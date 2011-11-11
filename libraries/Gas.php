<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Gas ORM Library
 *
 * A lighweight and easy-to-use ORM for CodeIgniter
 * 
 * This class intend to use as semi-native ORM library for CI, 
 * based on the ActiveRecord pattern. This library uses CI stan-
 * dard DB utility packages also validation class.
 *
 * @package     Gas Library
 * @category    Libraries
 * @version     1.2.1
 * @author      Taufan Aditya A.K.A Toopay
 * @link        http://taufanaditya.com/gas-orm
 * @license     BSD
 */

 /* ------------------------------------------------------------------------------------------------- */
 /* ------------------------------------------------------------------------------------------------- */

/**
 * Gas Core Class.
 *
 * @package     Gas Library
 * @subpackage	Gas Core
 * @category    Libraries
 * @version     1.2.1
 */

class Gas_Core {

	const GAS_VERSION = '1.2.1';

	public $table = '';

	public $primary_key = 'id';

	public $relations = array();

	public $errors = array();

	public $locked = FALSE;

	public $single = FALSE;


	protected $_fields = array();

	protected $_set_fields = array();

	protected $_get_fields = array();

	protected $_get_child_fields = array();

	protected $_get_child_nodes = array();


	public static $loaded_models = array();

	public static $childs = array();

	public static $childs_resource = array();

	public static $init = FALSE;

	public static $bureau;

	public static $ar_recorder = array();

	public static $post = FALSE;

	public static $join = FALSE;

	public static $with = FALSE;

	public static $with_models = array();

	public static $config;

	public static $transaction_pointer = FALSE;

	public static $selector = FALSE;

	public static $condition = FALSE;

	public static $executor = FALSE;

	public static $transaction_status = FALSE;

	public static $transaction_executor = FALSE;
	

	protected static $_models;

	protected static $_rules = array();

	protected static $_error_callbacks = array();
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		$init = FALSE;

		$gas = $this->model();

		if (self::is_initialize() == FALSE)
		{
			$CI =& get_instance();

			$CI->config->load('gas', TRUE, TRUE);

			self::$config = $CI->config->item('gas');
			
			$this->_scan_models();

			if (is_callable(array($this, '_init'), TRUE)) $this->_init();

			$init = TRUE;

			Gas_Core::$bureau = new Gas_Bureau($CI);
		}

		self::$bureau =& Gas_Core::recruit_bureau(); 

		self::$bureau->_models = self::$_models;

		self::$bureau->_config = Gas_core::$config;

		if (Gas_core::$config['autoload_models']) self::$bureau->load_model('*');

		if ($init) self::$init = TRUE;

		if (func_num_args() == 1)
		{
			$args = func_get_arg(0);

			if (isset($args['record'])) $this->_get_fields = Gas_Janitor::get_input(__METHOD__, $args['record'], FALSE, array());
		}

		log_message('debug', 'Gas ORM Core Class Initialized');
	}

	/**
	 * version
	 * 
	 * Gas Version
	 * 
	 * @access public
	 * @return string
	 */
	public static function version()
	{
		return Gas_Core::GAS_VERSION;
	}

	/**
	 * Produce an empty Gas model instance.
	 * 
	 * @access  public 
	 * @param   string 
	 * @return  object  Gas Instance
	 */
	public static function factory($name, $records = array())
	{
		$model = $name;

		$gas = new $model($records);

		if (is_callable(array($gas, '_init'), TRUE))
		{
			$gas->_init();

			self::$loaded_models[$name] = TRUE;
		}

		return $gas;
	}

	/**
	 * Database connection 
	 * 
	 * @access  public 
	 * @param   mixed 
	 * @return  void 
	 */
	public static function connect($dsn = null)
	{
		$dsn = Gas_Janitor::get_input(__METHOD__, $dsn, TRUE);

		return self::$bureau->connect($dsn);
	}

	/**
	 * recruit_bureau
	 * 
	 * Calling Gas Bureau instance
	 * 
	 * @access public
	 * @return object  Gas Bureau Instance
	 */
	public static function &recruit_bureau()
	{
		return Gas_Core::$bureau;
	}

	/**
	 * is_initialize
	 * 
	 * Check Gas Core State
	 * 
	 * @access public
	 * @return bool
	 */
	public static function is_initialize()
	{
		return self::$init;
	}

	/**
	 * field
	 * 
	 * Creates a validation rule for each used field(s)
	 * 
	 * @access public
	 * @param  string
	 * @param  array
	 * @return array
	 */
	public static function field($type = '', $args = array())
	{
		$rules = array();
		
		if (preg_match('/^([^)]+)\[(.*?)\]$/', $type, $m) AND count($m) == 3)
		{
			$type = $m[1];

			$rules[] = 'max_length['.$m[2].']';
		}
		
		switch ($type) 
		{
			case 'auto':

				$rules[] = 'callback_auto_check'; 

				break;
			
			case 'char':

				$rules[] = 'callback_char_check'; 

				break;
				
			case 'int':

				$rules[] = 'integer';

				break;
			
			case 'email':

				$rules[] = 'valid_email';

				break;
		}
		
		return array('rules' => implode('|', array_merge($rules, $args)));
	}

	/**
	 * db
	 * 
	 * Creates a temporary DB instance
	 * 
	 * @access public
	 * @return void
	 */
	public function db()
	{
		return Gas_Bureau::engine();
	}

	/**
	 * list_models
	 * 
	 * Get list of available models
	 * 
	 * @access public
	 * @return array
	 */
	public function list_models()
	{
		return self::$_models;
	}

	/**
	 * set_type
	 * 
	 * Get recent type value
	 * 
	 * @access public
	 * @return array
	 */
	public function set_type($type, $val)
	{
		return self::$$type = $val;
	}

	/**
	 * get_type
	 * 
	 * Get recent type value
	 * 
	 * @access public
	 * @return array
	 */
	public function get_type($type)
	{
		return self::$$type;
	}

	/**
	 * get_with
	 * 
	 * Get eager loading flag
	 * 
	 * @access public
	 * @return bool
	 */
	public function get_with()
	{
		return self::$with;
	}

	/**
	 * get_with
	 * 
	 * Get eager loading models
	 * 
	 * @access public
	 * @return array
	 */
	public function get_with_models()
	{
		return self::$with_models;
	}

	/**
	 * get_config
	 * 
	 * Get Gas configuration
	 * 
	 * @access public
	 * @return array
	 */
	public function get_config()
	{
		return self::$config;
	}

	/**
	 * with 
	 * 
	 * Eager loading pointer
	 * 
	 * @access public
	 * @return void
	 */
	public function with()
	{
		$args = func_get_args();

		$eager_load_models = Gas_Janitor::get_input(__METHOD__, $args, FALSE, array());

		if ( ! empty($eager_load_models))
		{
			self::$with = TRUE;

			self::$with_models = $eager_load_models;
		}

		return $this;
	}

	/**
	 * all
	 * 
	 * Fetch records
	 * 
	 * @access public
	 * @return object Gas Instance
	 */
	public function all()
	{
		$bureau = self::$bureau;

		$this->validate_table();
		
		$recorder = array('get' => array($this->table));

		Gas_Janitor::tape_record($this->model(), $recorder);

		$this->validate_join();

		return $bureau->compile($this->model(), self::$ar_recorder, $this->single);
	}
	
	/**
	 * find
	 * 
	 * Get record based by given primary key arguments
	 *
	 * @access  public
	 * @param   mixed
	 * @return  object Gas Instance
	 */
	public function find()
	{
		$args = func_get_args();

		$in = Gas_Janitor::get_input(__METHOD__, $args, TRUE);

		$this->single = count($in) == 1;

		return $this->find_where_in(array($this->primary_key, $in));
	}

	/**
	 * find_where_in
	 * 
	 * Get record based by given arguments
	 *
	 * @access  public
	 * @param   array
	 * @param   string
	 * @return  object Gas Instance
	 */
	public function find_where_in($args, $type = '')
	{
		$args = Gas_Janitor::get_input(__METHOD__, $args, TRUE);

		$this->where_in($args, $type);
		
		return $this->all();
	}

	/**
	 * find_where
	 * 
	 * Get record based by given arguments
	 *
	 * @access	public
	 * @param   array
	 * @param   int
	 * @param   int
	 * @param   string
	 * @return  object Gas Instance
	 */
	public function find_where($args, $limit = null, $offset = null, $type = '')
	{
		$args = Gas_Janitor::get_input(__METHOD__, $args, TRUE);

		if (is_int($limit))
		{
			if($limit == 1)  $this->single = TRUE;

			$recorder = array('limit' => array($limit, $offset));

			Gas_Janitor::tape_record($this->model(), $recorder);
			
		}

		if ($type == 'or')
		{
			$this->or_where($args);
		}
		else
		{
			$this->where($args);
		}

		return $this->all();
	}

	/**
	 * where
	 * 
	 * WHERE statement for conditional query
	 *
	 * @access  public
	 * @param   mixed
	 * @return  void
	 */
	public function where()
	{
		$args = func_get_args();

		$args = Gas_Janitor::get_input(__METHOD__, $args, TRUE);

		$recorder = array('where' => $args);
		
		Gas_Janitor::tape_record($this->model(), $recorder);

		return $this;
	}

	/**
	 * or_where
	 * 
	 * OR WHERE statement for conditional query
	 *
	 * @access  public
	 * @param   mixed
	 * @return  void
	 */
	public function or_where()
	{
		$args = func_get_args();

		$args = Gas_Janitor::get_input(__METHOD__, $args, TRUE);

		$recorder = array('or_where' => $args);
		
		Gas_Janitor::tape_record($this->model(), $recorder);

		return $this;
	}

	/**
	 * where_in
	 * 
	 * WHERE IN statement for conditional query
	 *
	 * @access  public
	 * @param   array
	 * @param   string
	 * @return  void
	 */
	public function where_in($args, $type = '')
	{
		$args = Gas_Janitor::get_input(__METHOD__, $args, TRUE);

		switch ($type)
		{
			case 'or':

				$recorder = array('or_where_in' => $args);

				break;

			case 'not';

				$recorder = array('where_not_in' => $args);

				break;

			case 'or_not';

				$recorder = array('or_where_not_in' => $args);

				break;

			default:

				$recorder = array('where_in' => $args);

				break;
		}
		
		Gas_Janitor::tape_record($this->model(), $recorder);

		return $this;
	}

	/**
	 * save
	 * 
	 * Save or Update a table
	 *
	 * @access  public
	 * @param   bool    whether to skip or not the validation process
	 * @return  int     affected rows
	 */
	public function save($check = FALSE)
	{
		$bureau = self::$bureau;

		$this->validate_table();

		if ($check)
		{
			if (is_callable(array($this, '_init'), TRUE)) $this->_init();

			$entries = is_array($this->_set_fields) ? array_merge($this->_get_fields, $this->_set_fields) : $this->_get_fields;

			if (is_callable(array($this, '_before_check'), TRUE)) $this->_before_check();

			$valid = $bureau->validate($this->model(), $entries, $this->_fields);

			if ( ! $valid) return FALSE;
		}

		if (is_callable(array($this, '_after_check'), TRUE)) $this->_after_check();

		if (is_callable(array($this, '_before_save'), TRUE)) $this->_before_save();

		if (empty($this->_get_fields))
		{
			$recorder = array('insert' => array($this->table, $this->_set_fields));
		}
		else
		{
			$identifier = $this->identifier();

			self::$ar_recorder = array();

			$recorder = array('where' => array($this->primary_key, $identifier));

			Gas_Janitor::tape_record($this->model(), $recorder);

			$this->_set_fields = array_merge($this->_get_fields, $this->_set_fields);

			$recorder = array('update' => array($this->table, $this->_set_fields));
		}

		Gas_Janitor::tape_record($this->model(), $recorder);

		Gas_Janitor::flush_post();

		$this->_set_fields = array();

		$bureau->compile($this->model(), self::$ar_recorder);

		if (is_callable(array($this, '_after_save'), TRUE)) $this->_after_save();

		return $this->db()->affected_rows();
		
	}

	/**
	 * delete
	 * 
	 * Delete record(s) based by given arguments
	 *
	 * @access  public
	 * @param   mixed
	 * @return  int     affected rows
	 */
	public function delete()
	{
		$bureau = self::$bureau;

		$this->validate_table();

		if (is_callable(array($this, '_init'), TRUE)) $this->_init();

		$args = func_get_args();

		$in = Gas_Janitor::get_input(__METHOD__, $args, FALSE, null);

		if (is_null($in)) 
		{
			$identifier = Gas_Janitor::get_input(__METHOD__, $this->identifier(), TRUE);

			$this->_set_fields = array($this->primary_key => $identifier);

			if (is_callable(array($this, '_before_delete'), TRUE)) $this->_before_delete();

			$recorder = array('delete' => array($this->table, $this->_set_fields));
		}
		else
		{
			$this->_set_fields = array($this->primary_key, $in);

			if (is_callable(array($this, '_before_delete'), TRUE)) $this->_before_delete();

			$this->where_in($this->_set_fields);

			$recorder = array('delete' => array($this->table));
		}

		Gas_Janitor::tape_record($this->model(), $recorder);

		Gas_Janitor::flush_post();

		$this->_set_fields = array();

		$bureau->compile($this->model(), self::$ar_recorder);

		if (is_callable(array($this, '_after_delete'), TRUE)) $this->_after_delete();

		return $this->db()->affected_rows();
	}

	/**
	 * tell
	 * 
	 * Gas Languange file utilizer.
	 * 
	 * @access public
	 * @param  string
	 * @param  string
	 * @return string
	 */
	public static function tell($point, $parser_value = null)
	{
		self::$bureau->lang()->load('gas');

		if (FALSE === ($msg = self::$bureau->lang()->line($point)))
		{
			$msg = '';
		}
		
		return (is_string($parser_value)) ? str_replace('%s', $parser_value, $msg) : $msg;
	}

	/**
	 * set_message
	 * 
	 * Creates a message for custom callback function
	 * Note: The key name has to match the  function name that it corresponds to.
	 * 
	 * @access public
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public static function set_message($key, $msg, $field = null)
	{
		$field = Gas_Janitor::get_input(__METHOD__, $field, TRUE);
		
		self::$bureau->lang()->load('form_validation');
		
		if (FALSE === ($line = self::$bureau->lang()->line($key)))
		{
			$line = $msg;
		}

		self::$_error_callbacks[] = str_replace('%s', Gas_Janitor::set_label($field), $line);
	}
	
	/**
	 * errors
	 *
	 * Gets the error(s) message associated with validation process
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function errors($prefix = '', $suffix = '')
	{
		$validator = self::$bureau->validator();
		
		$prefix = ($prefix == '') ? '<p>' : $prefix;

		$suffix = ($suffix == '') ? '</p>' : $suffix;
		
		$errors = '';

		foreach (self::$_error_callbacks as $error)
		{
			$errors .= $prefix.$error.$suffix."\n";
		}

		$str_errors = $errors.$validator->error_string($prefix, $suffix);

		return $str_errors;
	}
	
	/**
	 * auto_check (custom callback function for checking auto field)
	 *
	 * @access	public
	 * @param	string
	 * @param	mixed
	 * @return	bool
	 */
	public function auto_check($field, $val)
	{
		if (is_null($val) or is_integer($val) or is_numeric($val)) return TRUE;
		
		self::set_message('auto_check', 'The %s field was an invalid autoincrement field.', $field);
		
		return FALSE;
	}
	
	/**
	 * char_check (custom callback function for checking varchar/char field)
	 *
	 * @access	public
	 * @param	string
	 * @param	mixed
	 * @return	bool
	 */
	public function char_check($field, $val)
	{
		if (is_string($val) or $val === '') return TRUE;
		
		self::set_message('char_check', 'The %s field was an invalid char field.', $field);
		
		return FALSE;
	}

	/**
	 * to_array
	 * 
	 * Output array of model attributes
	 *
	 * @access	public
	 * @return	string
	 *
	 */
	public function to_array()
	{
		return Gas_Janitor::to_array($this->_get_fields);
	}

	/**
	 * to_json
	 * 
	 * Output json of model attributes
	 *
	 * @access	public
	 * @return	string
	 *
	 */
	public function to_json()
	{
		return Gas_Janitor::to_json($this->_get_fields);
	}

	/**
	 * set_fields
	 * 
	 * Set model attributes
	 *
	 * @access  public
	 * @param   mixed
	 * @return  array
	 *
	 */
	public function set_fields($resource)
	{
		$this->_set_fields = (array) $resource;
		
		return $this;
	}

	/**
	 * set_record
	 * 
	 * Set CI records into model attributes
	 *
	 * @access  public
	 * @param   mixed
	 * @return  void
	 */
	public function set_record($resource)
	{
		$this->_get_fields = (array) $resource;
		
		return $this;
	}

	/**
	 * set_child
	 * 
	 * Set Gas child properties
	 *
	 * @access  public
	 * @param   string
	 * @return  void
	 */
	public function set_child($name, $node)
	{
		array_push($this->_get_child_fields, $name);

		array_push($this->_get_child_nodes, array($name => $node));
		
		return $this;
	}

	/**
	 * add_ar_record
	 * 
	 * Push CI AR attributes to a Gas model
	 *
	 * @access  public
	 * @param   mixed
	 * @return  void
	 */
	public function add_ar_record($recorder)
	{
		array_push(self::$ar_recorder, $recorder);

		return $this;
	}


	/**
	 * set_ar_record
	 * 
	 * Set CI AR attributes to a Gas model
	 *
	 * @access  public
	 * @param   mixed
	 * @return  void
	 */
	public function set_ar_record($condition)
	{
		self::$ar_recorder = $condition;

		return $this;
	}

	/**
	 * get_ar_record
	 * 
	 * Get CI AR attributes of a Gas model
	 *
	 * @access	public
	 * @return	array
	 */
	public function get_ar_record()
	{
		return self::$ar_recorder;
	}

	/**
	 * model
	 * 
	 * Get Gas model name
	 *
	 * @access	public
	 * @return	string
	 *
	 */
	public function model($gas = null)
	{
		return is_null($gas) ? strtolower(get_class($this)) : strtolower(get_class($gas));
	}

	/**
	 * identifier
	 * 
	 * Get identifier key array
	 *
	 * @access	public
	 * @return	array
	 *
	 */
	public function identifier($column = null)
	{
		if ( ! is_null($column))
		{
			if (isset($this->_get_fields[$column]))
			{
				return $this->_get_fields[$column];	
			} 
			else
			{
				return;
			}
		}

		return $this->_get_fields[$this->primary_key];
	}

	/**
	 * validate_table
	 * 
	 * Validating whether current table is valid 
	 *
	 * @access  public
	 * @param   sring
	 * @return  void
	 */
	public function validate_table($table = null)
	{
		$table = (is_null($table)) ? $this->table : $table;
		
		if(empty($table))
		{
			$table = strtolower($this->model());
			
			$this->table = $table;
		}
		
		return $this;
	}

	/**
	 * validate_join
	 * 
	 * Validating whether JOIN statement has been declared
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function validate_join()
	{
		$this->validate_table();

		if (self::$join == TRUE)
		{
			self::$ar_recorder = Gas_Janitor::where_to_join(self::$ar_recorder, $this->table);
		}
		
		return $this;
	}


 	/**
	 * _scan_model
	 * 
	 * Scan model directories recursively and set global models collections
	 *
	 * @access	private
	 * @return	void
	 *
	 */
	private function _scan_models($path = null)
	{
		$models_dir = (is_null($path)) ? APPPATH.Gas_Core::$config['models_path'] : $path;

		if( ! is_dir($models_dir)) show_error(Gas_Core::tell('models_not_found', $models_dir));
		
		$files = scandir($models_dir);

		foreach ($files as $file) 
		{
		    if ($file == '.' OR $file == '..') continue;

		    $file = "$models_dir/$file";

		    if (is_dir($file))  $this->_scan_models($file);

		    if(strpos($file, Gas_Core::$config['models_suffix'].'.php') !== FALSE) 
			{
				$model = explode('/', $file);

				self::$_models[str_replace(Gas_Core::$config['models_suffix'].'.php', '', $model[count($model)-1])] = $file;
			}
		}
		
		return $this;
	}

	/**
	 * __set
	 * 
	 * Overloading method to writing data to inaccessible properties.
	 *
	 * @access  public
	 * @param   string
	 * @param   array
	 * @return  mixed
	 */
	function __set($name, $args)
	{
		$this->_set_fields[$name] = $args;
	}

	/**
	 * __get
	 * 
	 * Overloading method utilized for reading data from inaccessible properties.
	 *
	 * @access	public
	 * @param	string
	 * @return	mixed
	 *
	 */
	function __get($name) 
 	{
 		$this->validate_table();

 		if (isset($this->_get_fields[$name])) return $this->_get_fields[$name];

 		if ( ! empty($this->_get_child_fields))
 		{
 			foreach ($this->_get_child_fields as $index => $child)
 			{
 				if ($name == $child)
 				{
 					$type = Gas_Janitor::identify_relations($this->relations, $child);

 					if($type == 'has_one' or $type == 'belongs_to')
 					{
		 				return $this->_get_child_nodes[$index][$child];
 					}
 					else
 					{
 						return ( ! $this->_get_child_nodes[$index][$child]) ? array() : $this->_get_child_nodes[$index][$child];
 					}
	 			}
 			}
 		}

 		if (isset(self::$_models[$name]) and ! isset($this->_set_fields[$name]))
 		{
 			$link = array();

 			list($parent_table, $parent_key, $parent_relations) = Gas_Janitor::identify_meta($this->model());

 			list($child_table, $child_key, $child_relations) = Gas_Janitor::identify_meta($name);

 			$peer_relation = Gas_Janitor::get_input('Gas_Core::__get', Gas_Janitor::identify_relations($parent_relations, $name), FALSE, '');

			list($through, $custom_table, $custom_key, $self_ref) = Gas_Janitor::identify_custom_setting($parent_relations, $peer_relation, $name);

 			$foreign_table = $child_table;

 			$foreign_key = $child_key;

 			$identifier = ($custom_key !== '') ? $custom_key : $foreign_table.'_'.$foreign_key;
			

 			if($through !== '')
 			{
 				$tree = array(

 					'relations' => $child_relations,

 					'table' => $foreign_table,

 					'key' => $foreign_key,

 					'child' => $this->model(),
	 			);

 				$link = Gas_Janitor::identify_link($through, $identifier, $tree);

 				if ($peer_relations = 'belongs_to') $identifier = $parent_key;
 			}

 			return Gas_Bureau::generate_child($this->model(), $name, $link, array($this->identifier()), $this->identifier($identifier));
	 	}
 	}

 	/**
	 * __call
	 * 
	 * Overloading method triggered when invoking special method.
	 *
	 * @access	public
	 * @param	string
	 * @param	array
	 * @return	void
	 */
	function __call($name, $args)
	{
		$this->validate_table();

		$bureau = self::$bureau;

		$engine = $this->db();

		if (empty($this->table)) $this->table = $this->model();
		
		if ($name == 'fill')
		{

			$input = array();

			$raw_input = array_shift($args);

			$post = array_shift($args);

			if ($post)
			{
				self::$post = TRUE;

				$_POST = $raw_input;

				$this->_set_fields = $raw_input;

			}
			elseif (isset($_POST))
			{
				if ($_POST == $raw_input)
				{
					self::$post = TRUE;

					$this->_set_fields = $_POST;
				}
				else
				{
					$this->_set_fields = $raw_input;
				}
			}
			else
			{
				$this->_set_fields = $raw_input;
			}

			return $this;

		}
		elseif ($name == 'filled_fields')
		{

			return $this->_set_fields;

		}
		elseif (preg_match('/^find_by_([^)]+)$/', $name, $m) AND count($m) == 2)
		{

			$field = $m[1];

			$value = array_shift($args);

			$limit = array_shift($args);

			$offset = array_shift($args);
			
			return $this->find_where(array($field => $value), $limit, $offset);

		}
		elseif (preg_match('/^(min|max|avg|sum)$/', $name, $m) AND count($m) == 2)
		{

			if (empty($args)) $args = array($this->primary_key);
			
			$recorder = array('select_'.$m[1] => $args);
			
			Gas_Janitor::tape_record($this->model(), $recorder);

			$this->single = TRUE;

			return $this->all();

		}
		elseif (preg_match('/^(first|last)$/', $name, $m) AND count($m) == 2)
		{

			$column = array_shift($args);

			$order = is_null($column) ? $this->primary_key : $column;

			$by = ($m[1] == 'first') ? 'asc' : 'desc';

			$recorder = array('order_by' => array($order, $by));

			Gas_Janitor::tape_record($this->model(), $recorder);

			$this->single = TRUE;

			return $this->all();

		}
		elseif (preg_match('/^join_([^)]+)$/', $name, $m) AND count($m) == 2)
		{

			$joined_field = $m[1];

			$on = array_shift($args);
			
			$join_args = (is_string($on)) ? array($joined_field, $on) : array($joined_field);

			$recorder = array('join' => $join_args);

			Gas_Janitor::tape_record($this->model(), $recorder);

			self::$join = TRUE;
			
			return $this;

		}
		elseif (preg_match('/^([^)]+)_join_([^)]+)$/', $name, $m) AND count($m) == 3)
		{

			$allowed_type = array('left', 'right', 'outer', 'inner', 'left outer', 'right outer');

			$join_type = str_replace('_', ' ', $m[1]);

			$joined_field = $m[2];

			$on = array_shift($args);

			if (in_array($join_type, $allowed_type))
			{
				$join_args = (is_string($on)) ? array($joined_field, $on, $join_type) : array($joined_field, '', $join_type);
			}
			else
			{
				$join_args = (is_string($on)) ? array($joined_field, $on) : array($joined_field);
			}

			$recorder = array('join' => $join_args);

			Gas_Janitor::tape_record($this->model(), $recorder);

			self::$join = TRUE;
			
			return $this;

		}
		elseif ($name == 'last_id')
		{
			return $this->db()->insert_id();
		}
		elseif ($name == 'list_fields')
		{
			return $engine->list_fields($this->table);
		}
		elseif ($name == 'last_sql')
		{
			
			return $engine->last_query();

		}
		elseif ($name == 'all_sql')
		{
			
			return $engine->queries;

		}
		elseif ($name == 'group_by')
		{

			$recorder = array('group_by' => $args);

			Gas_Janitor::tape_record($this->model(), $recorder);

			$this->single = FALSE;

			return $this;

		}
		elseif ($name == 'like')
		{

			$recorder = array('like' => $args);

			Gas_Janitor::tape_record($this->model(), $recorder);

			$this->single = FALSE;

			return $this;

		}
		elseif (method_exists($engine, $name))
		{
			$executor = Gas_Janitor::$dictionary['executor'];

			$direct = array_splice($executor, -6);

			$tables = array_splice($executor, -2);

			$writes = array_splice($executor, -4);

			$argumental = $executor;

			if (in_array($name, $tables))
			{
				$args = array($this->table);
			}
			elseif (in_array($name, $argumental) or in_array($name, $writes))
			{
				$args = Gas_Janitor::get_input('Gas_Core::__call', $args, TRUE);
			}

			$recorder = array($name => $args);

			Gas_Janitor::tape_record($this->model(), $recorder);

			$medical_history = Gas_Janitor::diagnostic($name);

			if ($medical_history == 'executor' or $medical_history == 'transaction_status')
			{
				return $bureau->compile($this->model(), self::$ar_recorder);
			}
			elseif (strpos($name, 'join') !== FALSE)
			{
				self::$join = TRUE;
			}
			elseif ($name == 'limit')
			{
				if (isset($args[0]) and $args[0] == 1) $this->single = TRUE;
			}
			
			return $this;
		}
		
		return FALSE;
	}

}

 /* ------------------------------------------------------------------------------------------------- */
 /* ------------------------------------------------------------------------------------------------- */

/**
 * Gas Bureau Class.
 *
 * @package     Gas Library
 * @subpackage	Gas Bureau
 * @category    Libraries
 * @version     1.2.1
 */

class Gas_Bureau {

	public $_models = array();

	public $_loaded_models = array();
	
	protected $_CI;

	protected $_engine;

	protected static $db;

	protected static $validator;
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->_CI = func_get_arg(0);

		if ( ! isset($this->_CI->db))
		{
			$this->_engine = $this->_CI->load->database('default', TRUE);
		}
		else
		{
			$this->_engine = $this->_CI->db;
		}

		self::$db = $this->_engine;

		$this->_CI->Gas_DB = self::$db;

		if ( ! isset($this->_CI->form_validation)) $this->_CI->load->library('form_validation');

		self::$validator = $this->_CI->form_validation;

		log_message('debug', 'Gas ORM Bureau Class Initialized');
	}

	/**
	 * do_compile
	 * 
	 * Compile AR
	 *
	 * @access	public
	 * @param	string
	 * @param	array
	 * @param	bool
	 * @return	response
	 */
	public static function do_compile($gas, $recorder, $limit = FALSE)
	{
		$tasks = Gas_Janitor::play_record($recorder);

		foreach ($tasks as $type => $task)
		{
			if (Gas::factory($gas)->get_type($type) == TRUE)
			{
				foreach($task as $action)
				{
					$motor = get_class(self::$db);

					if (method_exists($motor, key($action)))
					{
						$method = key($action);

						$args = array_shift($action);

						$is_transaction = Gas::factory($gas)->get_type('transaction_pointer');

						if ($type == 'executor')
						{
							$executor = Gas_Janitor::$dictionary['executor'];

							$operations = array_splice($executor, -8);

							$writes = array_splice($executor, -4);

							if ($method == 'get')
							{
								$result = Gas_Janitor::force_and_get(self::$db, $method, $args);

								Gas::factory($gas)->set_ar_record(array());

								return self::generator($gas, $result->result(), __FUNCTION__, $limit, Gas::factory($gas)->get_with());
							}
							elseif ( ! $is_transaction and in_array($method, $writes))
							{
								Gas_Janitor::force(self::$db, $method, $args);

								Gas::factory($gas)->set_ar_record(array());
								
								return self::$db->affected_rows();
							}
							
							Gas_Janitor::force(self::$db, $method, $args);

							Gas::factory($gas)->set_ar_record(array());
						}
						elseif ($type == 'transaction_executor')
						{
							Gas_Janitor::force(self::$db, $method, $args);

							Gas::factory($gas)->set_type('transaction_pointer', FALSE);

							Gas::factory($gas)->set_type('transaction_executor', FALSE);
						}
						elseif ($type == 'transaction_status')
						{
							return Gas_Janitor::force_and_get(self::$db, $method, $args);
						}
						else
						{
							Gas_Janitor::force(self::$db, $method, $args);
						}
					}
				}

			}
		}

		return;
	}

	/**
	 * engine
	 * 
	 * return CI DB Object
	 *
	 * @access	public
	 * @return	object
	 */
	public static function engine()
	{
		return self::$db;
	}

	/**
	 * generator
	 * 
	 * Generate Gas based by AR
	 *
	 * @access	public
	 * @param	string
	 * @param	mixed
	 * @param	string
	 * @param	bool
	 * @param	bool
	 * @param	bool
	 * @return	mixed
	 */
	public static function generator($gas, $resource, $method, $limit = FALSE, $with = FALSE, $locked = FALSE)
	{
		list($table, $primary_key, $relations) = Gas_Janitor::identify_meta($gas);

		$instances = array();

		$eager_load_models = array();

		$eager_load_results = array();

		if (empty($resource)) 
		{
			if ($limit == TRUE)
			{
				return  FALSE;
			}
			else
			{
				return  $instances;
			}
		}

		if ($with)
		{
			$childs = array();

			$eager_load_models = Gas::factory($gas)->get_with_models();

			foreach ($eager_load_models as $child)
			{
				list($t, $pk, $r) = Gas_Janitor::identify_meta($child);

				$childs[$child] = array(

					'foreign_table' => $t,

					'foreign_key' => $pk,

					'foreign_relations' => $r,
				);

			}

			$ids = array();

			$fids = array();

			foreach ($resource as $single)
			{
				if (isset($single->$primary_key)) $ids[] = $single->$primary_key;

				foreach ($childs as $child_model => $child)
				{
					$link = array();

					$peer_relations = Gas_Janitor::get_input(__METHOD__, Gas_Janitor::identify_relations($relations, $child_model), FALSE, '');

					list($through, $custom_table, $custom_key, $self_ref) = Gas_Janitor::identify_custom_setting($relations, $peer_relations, $child_model);

					$foreign_key = ($custom_key !== '') ? $custom_key : $child['foreign_table'].'_'.$child['foreign_key'];
					
					if ($through !== '')
		 			{
		 				$tree = array(

		 					'relations' => $child['foreign_relations'],

		 					'table' => $child['foreign_table'],

		 					'key' => $child['foreign_key'],

		 					'child' => $gas,
			 			);

		 				$link = Gas_Janitor::identify_link($through, $foreign_key, $tree);
			 			
			 			if ($peer_relations == 'belongs_to')$foreign_key = $primary_key;
		 			}

					if (isset($single->$foreign_key)) $fids[] = $single->$foreign_key;
				}

			}

			foreach ($eager_load_models as $model)
			{
				$eager_load_results[$model] = self:: generate_child($gas, $model, $link, $ids, array_unique($fids), TRUE);
			}

		}

		foreach ($resource as $record)
		{
			$model = ucfirst($gas);

			$instance = Gas::factory($model, array('record' => (array) $record));

			if ($with)
			{
				foreach ($eager_load_results as $child => $results)
				{
					$success = FALSE;

					$all_results = $results;

					$sample = is_array($results) ? array_shift($results) : array();

					if (empty($sample))
					{
						$instance->set_child($child, FALSE);
					}
					else
					{
						$identifier = $sample['identifier'];

						$self = $sample['self'];

						$eager_records = FALSE;

						foreach ($all_results as $result)
						{
							$eager_record = Gas_Janitor::get_input(__METHOD__, $result['record'], FALSE, array());

							$eager_type = Gas_Janitor::get_input(__METHOD__, $result['type'], FALSE, '');

							if (isset($result['raw']) and ! empty($result['raw']))
							{
								if (isset($result['raw'][$identifier]))
								{
									$id = Gas_Janitor::get_input(__METHOD__, $result['raw'][$identifier], FALSE, '');
								}
								else
								{
									$id = FALSE;
								}
								
							}
							else
							{
								$id = Gas_Janitor::get_input(__METHOD__, $eager_record[$identifier], FALSE, '');
							}

							list($eager_through, $eager_custom_table, $eager_custom_key, $eager_self_ref) = Gas_Janitor::identify_custom_setting($relations, $eager_type, $child);

							if ($eager_type == 'has_one' or $eager_type == 'has_many')
							{
								$key = $primary_key;
							}
							elseif ($eager_type == 'belongs_to')
							{
								$key = ($custom_key !== '') ? $custom_key : $primary_key;

								if ($eager_through) $key = $primary_key;
							}
							elseif ($eager_type == 'has_and_belongs_to')
							{
								$key = $primary_key;
							}

							if ($id == $record->$key)
							{
								if ($self)
								{
									$eager_records = Gas::factory($child, array('record' => $eager_record));

									continue;
								}
								else
								{
									$eager_records[] = Gas::factory($child, array('record' => $eager_record));
								}
							}
						}

						$instance->set_child($child, $eager_records);
					}
				}
			}

			if ($limit) return $instance;

			$instances[] = $instance;
		}

		return $instances;
	}

	/**
	 * generate_child
	 * 
	 * Generate Relationship Nodes
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	array
	 * @param	array
	 * @param	mixed
	 * @param	bool
	 * @return	mixed
	 */
	public static function generate_child($gas, $child, $link = array(), $identifiers = array(), $foreign_value = null, $eager_load = FALSE)
	{
		$global_identifier = '';

		list($table, $primary_key, $relations) = Gas_Janitor::identify_meta($gas);

		list($foreign_table, $foreign_key, $foreign_relations) = Gas_Janitor::identify_meta($child);

		if (empty($relations) or ! is_array($relations)) show_error(Gas_Core::tell('models_found_no_relations', $gas));

		$peer_relation = Gas_Janitor::get_input(__METHOD__, Gas_Janitor::identify_relations($relations, $child), FALSE, '');

		if (empty($peer_relation)) show_error(Gas_Core::tell('models_found_no_relations', $gas));


		list($through, $custom_table, $custom_key, $self_ref) = Gas_Janitor::identify_custom_setting($relations, $peer_relation, $child);

		$self = FALSE;

		if ( ! empty($link))
		{
			$origin_fields = self::$db->list_fields($foreign_table);

			self::$db->from($link['intermediate']);

			$join_link = $foreign_table.'.'.$link['child_key'].' = '.$link['child_identifier'];

			self::$db->join($foreign_table, $join_link);
		}

		if ($peer_relation == 'has_one' or $peer_relation == 'has_many')
		{
			$new_identifier = ($custom_key !== '') ? $custom_key : $table.'_'.$primary_key;

			$global_identifier = $new_identifier;

			if (empty($through)) self::$db->from($foreign_table);

			if (count($identifiers) == 1)
			{
				self::$db->where(array($new_identifier => $identifiers[0]));
			}
			elseif (count($identifiers) > 1)
			{
				self::$db->where_in($new_identifier, $identifiers);
			}	

			if ($peer_relation == 'has_one') $self = TRUE;
			
		}
		elseif ($peer_relation == 'belongs_to')
		{
			if ($self_ref)
			{
				$foreign_value = is_null($foreign_value) ? '' : $foreign_value;
			}

			$new_identifier = $foreign_key;

			$global_identifier = $new_identifier;

			if (empty($through))
			{
				self::$db->from($foreign_table);
			}
			else
			{
				$new_identifier = $link['parent_identifier'];

				$global_identifier = $new_identifier;
			}

			if (is_string($foreign_value) or is_numeric($foreign_value))
			{
				self::$db->where(array($new_identifier => $foreign_value))->limit(1);
			}
			elseif (is_array($foreign_value))
			{
				self::$db->where_in($new_identifier, $foreign_value);
			}

			$self = TRUE;
		}
		elseif ($peer_relation == 'has_and_belongs_to')
		{
			if ($custom_table !== '')
			{
				$pivot_table = $custom_table;
			}
			else
			{
				$guess_table = Gas_Janitor::combine($table, $foreign_table);

				foreach ($guess_table as $link_table)
				{
					if (self::$db->table_exists($link_table))
					{
						$pivot_table = $link_table;

						continue;
					}
				}
			}
			
			$pivot_table = Gas_Janitor::get_input(__METHOD__, $pivot_table, TRUE);

			$origin_fields = self::$db->list_fields($foreign_table);

			$new_identifier = ($custom_key !== '') ? $custom_key : $table.'_'.$primary_key;

			$foreign_relations = Gas::factory($child)->relations;

			$foreign_type = Gas_Janitor::get_input(__METHOD__, Gas_Janitor::identify_relations($foreign_relations, $gas), FALSE, '');

			if (empty($foreign_type)) show_error(Gas_Core::tell('models_found_no_relations', 'has_and_belongs:'.$gas));

			list($through, $custom_foreign_table, $custom_foreign_key, $self_ref) = Gas_Janitor::identify_custom_setting($foreign_relations, $foreign_type, $gas);

			$foreign_identifier = ($custom_foreign_key !== '') ? $custom_foreign_key : $foreign_table.'_'.$foreign_key;

			$global_identifier = $new_identifier;

			self::$db->join($pivot_table, $foreign_identifier.' = '.$foreign_key);

			if (count($identifiers) == 1)
			{
				self::$db->where(array($new_identifier => $identifiers[0]))->from($foreign_table);
			}
			elseif (count($identifiers) > 1)
			{
				self::$db->where_in($new_identifier, $identifiers)->from($foreign_table);
			}	
		}

		$q = self::$db->get();

		$res = $q->result_array();

		if (count($res) > 0) 
		{

			$many = array();

			foreach ($res as $one)
			{
				$raw = array();

				if (isset($origin_fields))
				{
					if ($eager_load) $raw = $one;

					$keys = array_values($origin_fields);

					$values = array_keys($origin_fields);
					
					$levenshtein = array_combine($keys, $values);
					
					$one = array_intersect_ukey($one, $levenshtein, 'Gas_Janitor::intersect');
				}

				if($self and ! $eager_load) 
				{
					return Gas::factory($child, array('record' => $one));
				}

				if ($eager_load)
				{
					$many[] = array('identifier' => $global_identifier, 'self' => $self, 'type' => $peer_relation, 'record' => $one, 'raw' => $raw);
				}
				else
				{
					$many[] = Gas::factory($child, array('record' => $one));
				}
				
			}

			return $many;
		}
		else
		{
			return ($peer_relation == 'has_many' or $peer_relation == 'has_and_belongs_to') ? array() : FALSE;
		}
	}

	/**
	 * Database connection 
	 * 
	 * @param   mixed 
	 * @return  bool
	 */
	public function connect($dsn = null)
	{
		$this->_engine = $this->_CI->load->database($dsn, TRUE);
		
		if ( ! is_resource($this->_engine->simple_query("SHOW TABLES")))
		{
			show_error(Gas_Core::tell('db_connection_error', $dsn));
		}
	
		self::$db = $this->_engine;

		return;
	}

	/**
	 * compile
	 * 
	 * Dynamic function for ompile AR
	 *
	 * @access	public
	 * @param	string
	 * @param	array
	 * @param	bool
	 * @return	response
	 */
	public function compile($gas, $recorder, $limit = FALSE)
	{
		$result = self::do_compile($gas, $recorder, $limit);
		
		return $result;
	}

	/**
	 * load_model
	 * 
	 * Get model(s)'s public
	 *
	 * @access  public
	 * @param   mixed
	 * @return  void
	 */
	public function load_model($models = null)
	{
		if ($models == '*')
		{
			if(empty($this->_models)) return $this;
			
			foreach ($this->_models as $model => $model_path)
			{
				$this->_loaded_models[] = $model;

				require_once $model_path;
			}
		}
		elseif (is_array($models))
		{
			foreach ($models as $model)
			{
				if( ! array_key_exists($model, $this->_models)) show_error(Gas_Core::tell('models_not_found', $model));

				$this->_loaded_models[] = $model;

				require_once $this->_models[$model];
			}
		}
		elseif (is_string($models))
		{
			if ( ! array_key_exists($models, $this->_models)) show_error(Gas_Core::tell('models_not_found', $models));

			$this->_loaded_models[] = $models;

			require_once $this->_models[$models];
		}
		
		return $this;
	}

	/**
	 * validate
	 * 
	 * Validation handler
	 *
	 * @access  public
	 * @param   string
	 * @param   array
	 * @param   array
	 * @return  bool
	 */
	public function validate($gas, $entries, $rules)
	{
		$gas = Gas_Janitor::get_input(__METHOD__, $gas, TRUE);

		$entries = Gas_Janitor::get_input(__METHOD__, $entries, TRUE);

		$rules = Gas_Janitor::get_input(__METHOD__, $rules, TRUE);

		$validator = self::$validator;

		foreach ($rules as $field => $rule)
		{
			$validator->set_rules($field, Gas_Janitor::set_label($field), $rule['rules']);
		}

		if($validator->run() === TRUE)
		{
			$success = TRUE;
		}
		else
		{
			$success = FALSE;
		}

		foreach ($rules as $field => $rule)
		{
			if(strpos($rule['rules'], 'callback'))
			{
				foreach (explode('call', $rule['rules']) as $callback_rule)
				{
					if (substr($callback_rule, 0, 5) == 'back_')
					{
						$rule = substr($callback_rule, 5);
					
						if ( ! method_exists($gas, $rule))	continue;

						$entries_value = isset($entries[$field]) ? $entries[$field] : FALSE; 

						$success = Gas::factory($gas)->$rule($field, $entries_value);
					}
				}
			}
		}

		return $success;
	}

	/**
	 * validator
	 * 
	 * CI Form validation class object
	 *
	 * @access	public
	 * @return	object
	 */
	public function validator()
	{
		return self::$validator;
	}

	/**
	 * lang
	 * 
	 * CI Language class object
	 *
	 * @access	public
	 * @return	object
	 */
	public function lang()
	{
		return $this->_CI->lang;
	}
	
	/**
	 * __call
	 * 
	 * Overloading method triggered when invoking special method.
	 *
	 * @access	public
	 * @param	string
	 * @param	array
	 * @return	void
	 */
	function __call($name, $args)
	{
		$db = get_class(self::$db);

		if (is_callable($db.'::'.$name))
		{
			call_user_func_array(array(self::$db, $name), $args);
		}

		return $this;
	}

}

 /* ------------------------------------------------------------------------------------------------- */
 /* ------------------------------------------------------------------------------------------------- */

/**
 * Gas Janitor Class.
 *
 * @package     Gas Library
 * @subpackage	Gas Janitor
 * @category    Libraries
 * @version     1.2.1
 */

class Gas_Janitor {

	public static $dictionary = array(

		'transaction_pointer' => array('trans_off', 'trans_start', 'trans_begin'),

		'transaction_executor' => array('trans_complete', 'trans_rollback', 'trans_commit'),

		'selector' => array('select', 'select_max', 'select_min', 'select_avg', 'select_sum'),

		'condition' => array('join', 'where', 'or_where', 'where_in', 'or_where_in', 'where_not_in', 'or_where_not_in', 'like', 'or_like', 'not_like', 'or_not_like', 'group_by', 'distinct', 'having', 'or_having', 'order_by', 'limit', 'set'),

		'executor' => array('get', 'count_all_results', 'insert_string', 'update_string', 'query', 'insert', 'insert_batch', 'update', 'delete', 'empty_table', 'truncate', 'insert_id', 'count_all', 'affected_rows', 'platform', 'version', 'last_query'),

		'transaction_status' => array('trans_status'),

	);

	/**
	 * diagnostic
	 *
	 * @access  public
	 * @param   string
	 * @return  string
	 */
	public static function diagnostic($name)
	{
		foreach (self::$dictionary as $type => $nodes)
		{
			if (in_array($name, $nodes)) return $type;
		}

		return '';
	}

	/**
	 * identify_meta
	 *
	 * @access  public
	 * @param   string
	 * @return  array
	 */
	public static function identify_meta($gas)
	{
		$root = Gas::factory($gas);

		$table = self::get_input(__METHOD__, $root->validate_table()->table, TRUE);

		$primary_key = self::get_input(__METHOD__, $root->primary_key, TRUE);

		$relations = self::get_input(__METHOD__, $root->relations, FALSE, array());

		return array($table, $primary_key, $relations);
	}
	/**
	 * identify_link
	 *
	 * @access  public
	 * @param   string
	 * @param   string
	 * @param   array
	 * @return  array
	 */
	public static function identify_link($through, $identifier, $tree = array())
	{
		$through_table = Gas::factory($through)->validate_table()->table;

		$peer_relation = self::get_input(__METHOD__, self::identify_relations($tree['relations'], $tree['child']), TRUE);

		list($child_through, $child_custom_table, $child_custom_key, $child_self_ref) = self::identify_custom_setting($tree['relations'], $peer_relation, $tree['child']);

		$child_identifier = ($child_custom_key !== '') ? $child_custom_key : $tree['table'].'_'.$tree['key'];

		return array(

			'intermediate' => $through_table,

			'parent_identifier' => $identifier,

			'child_identifier' => $child_identifier,

			'child_key' => $tree['key'],

		);
	}

	/**
	 * identify_relations
	 *
	 * @access  public
	 * @param   array
	 * @param   string
	 * @return  string
	 */
	public static function identify_relations($relations, $child)
	{
		$peer_relation = null;

		foreach ($relations as $type => $relation)
		{
			foreach ($relation as $model => $config)
			{
				if ($model == $child)
				{
	 				$peer_relation = $type;

	 				continue;
				}
			}
		}

		return $peer_relation;
	}

	/**
	 * identify_custom_setting
	 *
	 * @access  public
	 * @param   array
	 * @param   string
	 * @return  array
	 */
	public static function identify_custom_setting($relations = array(), $type = '', $model = '')
	{
		$through = '';

		$custom_table = '';

		$custom_key = '';

		$self = FALSE;

		if ( ! empty ($relations[$type][$model]) and ($custom_setting = $relations[$type][$model]))
		{
			isset($custom_setting['through']) and $through = $custom_setting['through'];

			isset($custom_setting['foreign_table']) and $custom_table = $custom_setting['foreign_table'];

			isset($custom_setting['foreign_key']) and $custom_key = $custom_setting['foreign_key'];

			isset($custom_setting['self']) and $self = $custom_setting['self'];
		}

		return array($through, $custom_table, $custom_key, $self);
	}

	/**
	 * combine
	 *
	 * @access  public
	 * @param   array
	 * @return  array
	 */
	public static function combine($a, $b)
	{
		return array($a.'_'.$b, $b.'_'.$a);
	}

	/**
	 * intersect
	 *
	 * @access  public
	 * @param   array
	 * @return  array
	 */
	public static function intersect($a, $b)
	{
	    if ($a == $b)
	    {
	    	return 0;
	    }  
	    elseif ($a > $b)
	    {
	    	return 1;
	    }
	    
	    return -1;
	}

	/**
	 * new_record
	 *
	 * @access  public
	 * @return  array
	 */
	public static function new_record()
	{
		return array_fill(0, count(self::$dictionary), array());
	}

	/**
	 * tape_record
	 *
	 * @access  public
	 * @param   string
	 * @param   array
	 * @return  void
	 */
	public static function tape_record($gas, $recorder)
	{
		$success = FALSE;

		$recorder = self::get_input(__METHOD__, $recorder, FALSE, array());

		foreach (self::$dictionary as $type => $nodes)
		{
			foreach ($nodes as $node)
			{
				if (key($recorder) == $node)
				{
					$success = TRUE;

					Gas::factory($gas)->set_type($type, TRUE);

					Gas::factory($gas)->add_ar_record($recorder);
				}
			}
		}

		return;
	}

	/**
	 * play_record
	 *
	 * @access  public
	 * @param   array
	 * @return  array
	 */
	public static function play_record($recorder)
	{
		$blank_disc = self::new_record();

		$tasks = array_combine(array_keys(self::$dictionary), $blank_disc);

		foreach ($recorder as $task)
		{
			foreach (self::$dictionary as $type => $nodes)
			{
				foreach ($nodes as $node)
				{
					if (key($task) == $node)  array_push($tasks[$type], $task);
				}
			}
		}

		return $tasks;
	}

	/**
	 * force
	 *
	 * @access  public
	 * @param   string
	 * @param   string
	 * @param   array
	 * @return  void
	 */
	public static function force($class, $method, $args, $return = FALSE)
	{
		$total_args = count($args);
			
		if ($total_args == 4)
		{
			if ($return === TRUE) return $class->$method($args[0], $args[1], $args[2], $args[3]);

			$class->$method($args[0], $args[1], $args[2], $args[3]);
		}
		elseif ($total_args == 3)
		{
			if ($return === TRUE) return $class->$method($args[0], $args[1], $args[2]);

			$class->$method($args[0], $args[1], $args[2]);
		}
		elseif ($total_args == 2)
		{
			if ($return === TRUE) return $class->$method($args[0], $args[1]);

			$class->$method($args[0], $args[1]);
		}
		elseif ($total_args == 1)
		{
			if ($return === TRUE) return $class->$method($args[0]);

			$class->$method($args[0]);
		}
		else
		{
			if ($return === TRUE) return $class->$method();

			$class->$method();
		}

		return;
	}

	/**
	 * force_and_get
	 *
	 * @access  public
	 * @param   string
	 * @param   string
	 * @param   array
	 * @return  mixed
	 */
	public static function force_and_get($class, $method, $args)
	{
		return self::force($class, $method, $args, TRUE);
	}

	/**
	 * flush_post
	 *
	 * @access  public
	 * @return  void
	 */
	public static function flush_post()
	{
		if (isset($_POST)) $_POST = array();
	}

	/**
	 * get_input
	 *
	 * @access  public
	 * @param   mixed
	 * @param   bool
	 * @param   bool
	 * @return  mixed
	 */
	public static function get_input($method, $input, $die = FALSE, $default = FALSE)
	{
		if ( ! isset($input) or empty($input))
		{
			if ($die) show_error(Gas_Core::tell('empty_arguments', $method));

			$input = $default;
		}

		return $input;
	}
	
	/**
	 * to_array
	 *
	 * @access  public
	 * @param   mixed
	 * @return  array
	 */
	public static function to_array($var)
	{
		return (array) $var;
	}

	/**
	 * to_json
	 *
	 * @access  public
	 * @param   array
	 * @return  string
	 */
	public static function to_json($var)
	{
		return json_encode($var);
	}

	/**
	 * where_to_join
	 *
	 * @access  public
	 * @param   array
	 * @param   string
	 * @return  array
	 */
	public static function where_to_join($recorder, $table)
	{
		$condition = self::$dictionary['condition'];

		foreach ($condition as $node)
		{
			foreach ($recorder as $index => $statement)
			{
				$preserve = array('join', 'select');

				$type = key($statement);

				if ( ! in_array($type, $preserve) and $type == $node)
				{
					$recorder[$index][$node][0] = $table.'.'.$recorder[$index][$node][0];
				}
			}
		}

		return $recorder;
	}

	/**
	 * to_json
	 *
	 * @access  public
	 * @param   string
	 * @return  string
	 */
	public static function set_label($field)
	{
		return str_replace(array('-', '_'), ' ', ucfirst($field));
	}

}

 /* ------------------------------------------------------------------------------------------------- */
 /* ------------------------------------------------------------------------------------------------- */

/**
 * Gas Class.
 *
 * @package     Gas Library
 * @subpackage	Gas
 * @category    Libraries
 * @version     1.2.1
 */

class Gas extends Gas_Core {}