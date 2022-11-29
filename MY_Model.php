<?php

/**
 * CodeIgniter CRUD Model 
 * Modelo Base para CRUD
 *
 * Instalar en application/core/MY_Model.php
 *
 * @package    CodeIgniter
 * @author     Marcelo Audisio
 * @link       https://github.com/MarceloAudisio
 *
 *
 */
class MY_Model extends CI_Model
{
	// Tabla a construir el modelo
	public $table;
	// Clave Primaria de la tabla
    public $primary_key;
	// Consulta actual
    public $query;
    // Array de Datos
    public $db_array=null;
	
	// Campo para Fecha de creación (se omite si no existe)
	public $date_created_field;
	
	// Campo para Fecha de ultima modificación (se omite si no existe)
    public $date_modified_field;
	
    //Valores para orden y filtrado
	public $order=array();
	public $search=array();
	
	// Total de registros en la consulta (para paginación)
    public $total_rows;
    
	// Métodos existentes en CI_Model (no serán sobreescritos por este modelo)
    public $native_methods = array(
        'select', 'select_max', 'select_min', 'select_avg', 'select_sum', 'join',
        'where', 'or_where', 'where_in', 'or_where_in', 'where_not_in', 'or_where_not_in',
        'like', 'or_like', 'not_like', 'or_not_like', 'group_by', 'distinct', 'having',
        'or_having', 'order_by', 'limit'
    );
	
    public $filter = array();

    public function __call($name, $arguments)
    {
        if (substr($name, 0, 7) == 'filter_') {
            $this->filter[] = array(substr($name, 7), $arguments);
        } else {
            call_user_func_array(array($this->db, $name), $arguments);
        }
        return $this;
    }

    /*
	* Funciones para determinar el orden en la funcion get()
	*/

    /*
    * Establece el orden de consulta (se pueden poner varios)
    * @param $campo Campo a ordenar
    * @param $sentido Orden ASC o DESC
    */
	public function set_order($campo,$sentido="asc"){
		$this->order[]=array("campo"=>$campo,"sentido"=>$sentido);
	}

    /*
    * Limpiar Orden
    */
	public function clear_order(){
		$this->order=array();
	}
	
    /*
    * Rutina para tomar los campos de orden
    */
	public function ordenar(){
		foreach($this->order as $o){
			$this->db->order_by($o["campo"],$o["sentido"]);
		}
	}

    
	/*
	* Funciones para Buscar
	*/

    /*
    * Establecer campos a buscar
    * @param $campo Campo en el que se va a buscar
    * @param $termino valor a buscar
    * @param $patron 'both' (default): LIKE '%$termino%', 'before': LIKE '%$termino', 'after': LIKE '$termino%'
    */

	public function set_search($campo,$termino,$patron='both'){
		$this->search[]=array("campo"=>$campo,"termino"=>$termino,"patron"=>$patron);
	}

	public function clear_search(){
		$this->search=array();
	}

	public function buscar(){
		if(count($this->search)){
			$this->db->group_start();
				$buscar_temp=$this->search;
				$primer_termino=array_shift($buscar_temp);
				$this->db->like($primer_termino["campo"],$primer_termino["termino"],$primer_termino["patron"]);
				foreach($buscar_temp as $b){
					$this->db->or_like($b["campo"],$b["termino"],$b["patron"]);
				}
			
			$this->db->group_end();
		}
	}

    /*
    * Establecer máximo de registros 
    * @param $limit Maximo de registros a obtener en la consulta
    * @param $offset Desplazamiento antes de tomar el límite
    */
	public function set_limit($limit=false,$offset=0){
		$this->registros_por_pagina=$limit;
		$this->avance=$offset;
	}

	/*
	* Funciones para paginacion
	*/

	/*
    * Metodo que fuerza el retorno de todos los resultados (sin paginación)
    */
	public function desactivar_paginacion(){
		$this->registros_por_pagina=false;
	}

	/*
    * Establecer tamaño de página (cantidad de registros)
    */
	public function set_registros_por_pagina($valor=""){
		$this->registros_por_pagina=$valor;
	}

	/*
    * Establecer el avance
    */
	public function set_avance($valor=""){
		$this->avance=$valor;
	}

	/*
    * Obtenter el total de registros (tiene que calcularse primero con get())
    */
	public function obtener_total_registros(){
		return $this->total_registros;
	}

    /**
     * Obtener resultados
     */
    public function get($include_defaults = true)
    {
        if ($include_defaults) {
            $this->set_defaults();
        }

        $this->run_filters();

        //Si la variable esta en FALSE la paginación no aplica, se devuelven todos los resultados
		if($this->registros_por_pagina){
			$this->db->limit($this->registros_por_pagina, $this->avance);
		}
		
		//Ordenamiento
		if(count($this->orden)){
			$this->ordenar();
		}

        //Definir Busqueda (Si esta seteada)
		$this->buscar();

        $this->query = $this->db->get($this->table);

        $this->filter = array();

        return $this;
    }

    private function run_filters()
    {
        foreach ($this->filter as $filter) {
            call_user_func_array(array($this->db, $filter[0]), $filter[1]);
        }

        /**
         * Limpiar los filtros 
         */
        $this->filter = array();
    }

    /**
     * Defaults en los modelos hijos
     * @param $exclude Array con métodos que NO serán procesados
     */
    private function set_defaults($exclude = array())
    {
        $native_methods = $this->native_methods;

        foreach ($exclude as $unset_method) {
            unset($native_methods[array_search($unset_method, $native_methods)]);
        }

        foreach ($native_methods as $native_method) {
            $native_method = 'default_' . $native_method;

            if (method_exists($this, $native_method)) {
                $this->$native_method();
            }
        }
    }
	
    /**
     * Obtener registro por id
     * @param $id Id del Registro a obtener
     * @param $array Resultado en formato de array (default) u objeto (si se pasa como false)
     */
    public function get_by_id($id,$array=true)
    {
        if($array){
			return $this->where($this->primary_key, $id)->get()->row_array();
		}else{
			return $this->where($this->primary_key, $id)->get()->row();
		}
	}

	/**
     * Obtener registro basado en campo
     * @param text $field Campo a buscar
     * @param $value Valor a buscar (match exacto)
     * @param $array Resultado en formato de array (default) u objeto (si se pasa como false)
     */
    public function get_by_field($field,$value,$array=true)
    {
	    if($array){
			return $this->where($field, $value)->get()->row_array();
		}else{
			return $this->where($field, $value)->get()->row();
		}
	}
	
    /**
     * Copiar registro basado en id 
     * @param int $id Id de registro a copiar
     * @param array $modified_data Datos a modificar en la copia (false=copia exacta)
     */
	public function copyrecord($id,$modified_data=false){
		$origen=$this->where($this->primary_key, $id)->get()->row_array();
		unset($origen[$this->primary_key]);
		if(is_array($modified_data)){
			$origen=array_replace($origen,$modified_data);
		}
		$this->db->insert($this->table, $origen);
		return $this->db->insert_id(); 
	}
	
    /**
     * Grabar registro
     * @param int $id Id de registro a modificar ($id= null crea uno nuevo)
     * @param array $db_array Datos a modificar o insertar
     */
    public function save($id = null, $db_array = null)
    {
        //Si no se pasa el array como parámetro, se busca como atributo del objeto
        if (!$db_array) {
            $db_array = $this->db_array();
        }
        $datetime = date('Y-m-d H:i:s');

        //Si no hay $id se crea un regsitro, sinó se actualiza
        if (!$id) {
            if ($this->date_created_field) {
                if (is_array($db_array)) {
                    $db_array[$this->date_created_field] = $datetime;

                    if ($this->date_modified_field) {
                        $db_array[$this->date_modified_field] = $datetime;
                    }
                } else {
                    $db_array->{$this->date_created_field} = $datetime;

                    if ($this->date_modified_field) {
                        $db_array->{$this->date_modified_field} = $datetime;
                    }
                }
            } elseif ($this->date_modified_field) {
                if (is_array($db_array)) {
                    $db_array[$this->date_modified_field] = $datetime;
                } else {
                    $db_array->{$this->date_modified_field} = $datetime;
                }
            }

            $this->db->insert($this->table, $db_array);

            return $this->db->insert_id();
        } else {
            if ($this->date_modified_field) {
                if (is_array($db_array)) {
                    $db_array[$this->date_modified_field] = $datetime;
                } else {
                    $db_array->{$this->date_modified_field} = $datetime;
                }
            }

            $this->db->where($this->primary_key, $id);
            $this->db->update($this->table, $db_array);

            return $id;
        }
    }

    /**
     * Borra 1 o mas registros basado en la clave primaria
     * @param $ids si es un único valor borra un registro, si es un array, borra varios
     */
    public function delete($ids)
    {
        if(is_array($ids)){
            $this->db->where_in($this->primary_key, $id);
        }else{
            $this->db->where($this->primary_key, $id);
        }
        
        $this->db->delete($this->table);
        return $this->db->affected_rows();
    }

    /**
     * Devuelve los resultados como objetos
     */
    public function result()
    {
        return $this->query->result();
    }

    /**
     * Devuelve el resultado como objeto
     */
    public function row()
    {
        return $this->query->row();
    }

    /**
     * Devuelve los resultados como array
     */
    public function result_array()
    {
        return $this->query->result_array();
    }

    /**
     * Devuelve el resultado como array
     */
    public function row_array()
    {
        return $this->query->row_array();
    }

    /**
     * Devuelve el total de filas de la consulta
     */
    public function num_rows()
    {
        return $this->query->num_rows();
    }

}

?>