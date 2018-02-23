<?php
define('DBNAME', '');
define('DBUSER', '');
define('DBPASS', '');
define('TABLEX_PREFIX', 'modx_');
define('SERVERHOST', 'localhost');
define('SECRET', '');
define('BACKUPS_PATH', '/home/site.ru/backups/db/');

$die = true;
if (isset($_GET['code']) && $_GET['code'] == SECRET) {
	$die = false;
} elseif ($argc == 2 && $argv[1] == SECRET) {
    $die = false;
}
if ($die) {
	die();
}

class MySql {
	private $dbc;
	private $user;
	private $pass;
	private $dbname;
	private $host;

	function __construct($host, $dbname, $user, $pass){
		$this->user = $user;
		$this->pass = $pass;
		$this->dbname = $dbname;
		$this->host = $host;
		$opt = array(
		   PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		   PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);
		try{
			$this->dbc = new PDO('mysql:host='.$this->host.';dbname='.$this->dbname.';charset=utf8', $user, $pass, $opt);
		}
		catch(PDOException $e){
			 echo $e->getMessage();
			 echo 'There was a problem with connection to db check credenctials';
		}
	}

	public function truncateTables() {
		$tables = [
			TABLEX_PREFIX.'session',
		];
		
		foreach ($tables as $table) {
			$statement = $this->dbc->prepare('TRUNCATE TABLE `'.$table.'`');
			$statement->execute();
		}
	}

	public function backup_tables($tables = '*') {
		$this->truncateTables();
	
		$host=$this->host;
		$user=$this->user;
		$pass=$this->pass;
		$dbname=$this->dbname;
		$data = '';

		if($tables == '*')
		{
			$tables = array();
			$result = $this->dbc->prepare('SHOW TABLES');
			$result->execute();
			while($row = $result->fetch(PDO::FETCH_NUM))
			{
				$tables[] = $row[0];
			}
		}
		else
		{
			$tables = is_array($tables) ? $tables : explode(',',$tables);
		}

		foreach($tables as $table)
		{
			$resultcount = $this->dbc->prepare('SELECT count(*) FROM '.$table);
			$resultcount->execute();
			$num_fields = $resultcount->fetch(PDO::FETCH_NUM);


			$num_fields = $num_fields[0];

			$result = $this->dbc->prepare('SELECT * FROM '.$table);
			$result->execute();
			$colcount = $result->columnCount();
			$data.= 'DROP TABLE IF EXISTS '.$table.';';

			$result2 = $this->dbc->prepare('SHOW CREATE TABLE '.$table);
			$result2->execute();
			$row2 = $result2->fetch(PDO::FETCH_NUM);
			$data.= "\n\n".$row2[1].";\n\n";

			while($row = $result->fetch(PDO::FETCH_NUM))
			{
				$data.= 'INSERT INTO '.$table.' VALUES(';
				for ($j = 0; $j < $colcount; $j++)
				{
					$row[$j] = addslashes($row[$j]);
					$row[$j] = str_replace("\n","\\n",$row[$j]);
					if (isset($row[$j])) { $data.= '"'.$row[$j].'"' ; } else { $data.= '""'; }
					if ($j<($colcount-1)) { $data.= ','; }
				}
				$data.= ");\n";
			}
			$data.="\n\n\n";
		}
		
		$filename = rtrim('/', BACKUPS_PATH).'/'.TABLEX_PREFIX.date('Y-m-d').'_'.md5(time()).'.sql';
		$this->writeUTF8filename($filename,$data);
	}

	private function writeUTF8filename($filenamename, $content){  /* save as utf8 encoding */
		$f = fopen($filenamename,"w+");
		fwrite($f, pack("CCC",0xef,0xbb,0xbf));
		fwrite($f,$content);
		fclose($f);
	}

	public function recoverDB($file_to_load){
		echo 'write some code to load and proccedd .sql file in here ...';
	}

	public function closeConnection(){
		$this->dbc = null;
	}
}

$x = new MySql(SERVERHOST, DBNAME, DBUSER, DBPASS);
$x->backup_tables();
?>
