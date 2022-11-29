 CodeigniterCRUD_model
# Crud Model Base for Codeigniter 3

Se trata de un modelo para adjuntar a un proyecto de codeiniter 3, con la mayoría de los problemas comunes resueltos

###### Como usar:
Copiar el archivo MY_Model.php al directorio application/core/

Al crear un modelo, este es el esquema básico

```
class example_model extends MY_Model{
    public $table="tabla";
    public $primary_key="tabla.id";
    
    public function __construct(){
         parent::__construct();
    }
    
    //Defaults example
    public function default_select(){
         $this->db->select(*);
    } 
    public function default_join(){
         $this->db->join("tabla2",tabla2.fk_id=$this->primarykey,"inner");
    }

}
```
