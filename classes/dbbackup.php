<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Simples db-backup para Kohana
 *
 * @author      Bernardo Castro <bs.castro@gmail.com>
 * @author      Sudeste on <github.com/sudeste>
 * @version     1.0.2
 */
class DbBackup{

    // Mysqldump Options
	protected $opt = array();
	
	// Backup File name
	protected $filename;
	
    // Caminho completo Backup File name
    protected $path;
	
    // Extensão
    protected $ext;
    
    
	/**
	 * Object Instância
	 *
	 * @param string $path para salvar o arquivo
	 * @param string $group_db do config database
	 */
	public static function factory($path, $group_db = 'default') {

		return new DbBackup($path, $group_db);
	}
	
	
	public function __construct($path, $group_db) {
		
		$database = Kohana::$config->load('database.'.$group_db);
		$connection = $database['connection'];
				
		$this->opt = array(
		    'group_db' => $group_db,
		  	'hostname' => $connection['hostname'],
	        'user'     => $connection['username'],
	        'password' => $connection['password'],
	        'database' => $connection['database'],
		);
		
		$this->path     = $path;
		$this->filename = $connection['database'];
		$this->ext      = '.sql';
	}
	
	
	/**
	 * Gera Backup por Mysqldump
	 * Retorna o caminho completo do arquivo SQL gerado.
	 *
	 * @return string full path
	 */
    public function mysqldump() {
    	
    	// $command = '/usr/bin/mysqldump ';
    	$command  = 'mysqldump ';
    	$command .= $this->set_options();
    	
    	exec($command);
    	
    	return $this->full_path();
    }
    
        
    /**
     * Gera backup por Select table
     * Retorna o caminho completo do arquivo SQL gerado.
     *  * Orientação: http://davidwalsh.name/backup-mysql-database-php
     *
     * @return string full path
     */
    public function mysqlselect() {
    	    	 
    	$show_tables = DB::query(Database::SELECT, 'SHOW TABLES')
    	->execute($this->opt['group_db'])
    	->as_array();
        
    	
    	// Lista as tabelas em array
        $tables = array();
        
        foreach ($show_tables as $key => $value)
        {
            $tabela_value = array_values($value);
            
            $tables[] = $tabela_value[0];
        }
        
        // Alinhas as tabelas
        $tables = $this->order_tables($tables);
        
        $output = '';
        
        foreach ($tables as $table)
        {
        	        	        	
        	// Criando as tabelas
            $create_table = DB::query(Database::SELECT, 'SHOW CREATE TABLE '.$table)
            ->execute()->as_array();
            
            $row2 = $create_table[0]['Create Table'];
            
            $output .= '-- Criando tabela '. $create_table[0]['Table'];
            $output  .= "\n\n";
            
            $output .= 'DROP TABLE IF EXISTS `'.$table.'`; ';
            $output  .= "\n";
            $output .= $row2.';';
            $output  .= "\n";
            $output .= 'LOCK TABLES `'.$table.'` WRITE;';
            $output  .= "\n\n";
        	
            
            // Verifica se tabela tem dados
            $count = DB::select('COUNT("*") AS count')->from($table)
            ->execute()->get('count');
                                                      
            if ($count)
            {
                // Inserindo os dados
                $r_column = DB::query(Database::SELECT, 'SHOW COLUMNS FROM '.$table)
                ->execute()->as_array();
               
                
                $col = array();
                
                foreach ($r_column as $key => $coluna)
                {
                    $col[] = $coluna['Field'];
                }
                
                
                $insert = DB::insert($table, $col);
                
                $valores = DB::select('*')->from($table)
                ->execute()->as_array();
                
                foreach ($valores as $key => $valor)
                {
                    $insert->values( array_values( $valor) );
                }
                            
                $row3 = (string) $insert;
            }
            else
            $row3 = '-- Tabela empty';

            
            $output .= "\n";
            $output .= $row3.';';
            $output .= "\n\n";
        }
        
        
        // Gera arquivo
        $this->salve_file($output);
        
        // Full path
        return $this->full_path();
    }
    
    
    
    ### Internas ###
    
    /**
     * Salva o arquivo do select
     *
     */
    private function salve_file($output) {
    	
    	// Gera Arquivo
        $path = $this->full_path();
        
        $handle = fopen($path, 'w+');
        fwrite($handle, $output);
    }
    
            
    /**
     * Coloca as tabelas `users` e `roles` no topo.
     * Corrige o erro ao importar por mysqladmin relacionado ao FOREIGN KEY.
     * Útil somente ao usar Kohana AUTH.
     *
     * @param array tables
     * @return array tables aligned
     */
    private function order_tables( array $tables) {
    	
    	foreach ($tables as $key => $value)
        {
            if ($value == 'users' OR $value == 'roles' )
            {
                // Apaga o array
                unset( $tables[$key] );
                            
                array_unshift($tables, $value);
            }
            
        }
        
        return $tables;
    }
    
    
    /**
     * Mysqldump - Cria opões
     * Opção [--opt --host=localhost --user=username --password=passwd db > path/db_date.sql]
     *
     * @return string options
     */
    private function set_options() {
    	
    	$opt = '--opt';
    	$opt .= ' --host='.$this->opt['hostname'];
    	$opt .= ' --user='.$this->opt['user'];
    	$opt .= ' --password='.$this->opt['password'];
    	$opt .= ' '.$this->opt['database'];
    	$opt .= ' > ';
    	
    	$opt .= $this->full_path();
    	
    	return $opt;
    }
    
        
    /**
     * Retorna data para colocar no filename
     *
     * @return string date
     */
    private function get_date() {
    	
    	return date('dmy-Hi');
    }
    
    
    /**
     * Retorna o caminho para salvar o arquivo SQL
     * Verifica se o path esta com '/' no final.
     *
     * @return string caminho destino
     */
    private function get_path() {
    	
    	$explode = explode('/', $this->path);
    	
    	if ( end($explode) )
    	{
    		return $this->path.'/';
    	}
    	else
    	{
    		return $this->path;
    	}
    	
    }
    
    
    /**
     * Retorna o caminho completo do arquivo
     *
     * @return string full path
     */
    private function full_path() {
    	
    	$full_path  =  $this->get_path() . $this->filename;
    	$full_path .= '_';
    	$full_path .= $this->get_date();
        $full_path .= $this->ext;
        
        return $full_path;
    }
    

}