<?php 
/**
 * Joey Albert Abano
 * 
 * A library for CodeIgniter, that helps generate Create, Read, Update and Delete 
 * database functions. Tightly integrated with jquery and primefaces api. 
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2015, Joey Albert Abano
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	
 * @author	Joey Albert Abano
 * @copyright	Copyright (c) 2015, Joey Albert Abano
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	http://projects.rankaru.com
 * @since	Version 0.1.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

@include_once( BASEPATH . 'core/Model.php' );

/**
 * Joey Albert Abano Crud Class
 *
 * This enables quick CRUD display and integration
 *
 * @package		ci.application.libraries
 * @subpackage	Libraries
 * @category	Database Manipulation
 * @author		Joey Albert Abano
 * @link		https://github.com/joeyapa/Code-Igniter-Prime-UI-CRUD-Library
 *   
 * 
*/
class Crud extends CI_Model {
	
	/**
	 * 
	 * Configuration file this CRUD CI Plugin
	 * 
	 * size:[int=20]
	 *   - Identify the size of the tables
	 * 
	 * @var array
	 */
	public $config = array('size'=>20); 
	
	
	
	// --------------------------------------------------------------------
	
	const _DATATABLE_REQUEST_ID = 'ci-crud-datatable';
	const _ACTION_REQUEST_ID = 'ci-crud-action';
	
	
	
	// --------------------------------------------------------------------
	
	/**
	 * CI Singleton
	 *
	 * @var object
	 */		
	protected $CI;
		
	// --------------------------------------------------------------------
	
	/**
	 * 
	 * Class constructor
	 *
	 * Loads the crud constructor.
	 *
	 *
	 * @param	array	$config	Crud options
	 * @return	void
	 */
	public function __construct($config = array())
	{
		// define the access of CI instances
		$this->CI =& get_instance();
		
		// define configuration file
		$this->config = array_merge($this->config, $config);
		
		// load helper, library, driver used by crud
		$this->load->helper('form');
		$this->load->helper('string');
		$this->CI->load->library('table');		
		$this->CI->load->driver('cache', array('adapter' => 'apc', 'backup' => 'file'));
		
		// scan through the table		
		log_message('info', '[Crud.__construct] Crud Class Initialized');
	}
	
	
	/**
	 * Encode the result and display the json value 
	 * 
	 * @param unknown $json
	 */
	private function encoderesult($datatable)	
	{
		echo json_encode($datatable);
		exit();
	}
	
	/**
	 * 
	 * @return multitype:array
	 */
	private function inputarray()
	{
		$data = array();		
		foreach($_POST as $key => $value){
			$data[$key] = $this->input->post($key);
		}
		return $data;
	}
	
	
	/**
	 * 
	 * Generates the crud forms 
	 * 
	 * @param arguments	$param   
	 *   $sql: required,(string) value can be an sql or table name
	 *   
	 * @return string	
	 */
	public function datatable()
	{
		$sql = '';
		
		// Process arguments
		$i = 0;
		foreach (func_get_args() as $param) {
			switch ($i)  {
				case 0: 
					// i.1 identify if the string parameter is a table name or an sql
					if ( gettype($param) == 'string' ) {
						$param = trim($param);						
						$sql = ( strripos($param, 'select') === FALSE ) ? ('SELECT * FROM ' . $param) : $param;
						//  
					}
					else if ( gettype($param) == 'array' ) {
						// if array handle the following attribute sql(string), table(string), columns(array), pagingtop(boolean), pagingbottom(boolean)
					}					 
					break;
				// case 1: array that contains the column name to header name mapping key(column name) value pair(title)										
			}
			$i++;
		}
		
		// Process the query
		$sqlTree = $this->selectSqlTreeParser($sql); 
		
		// Process request
		return $this->process($sqlTree);
	}
	
	/**
	 * 
	 * @param object $selectSqlTree
	 * @return string
	 */
	private function process($sqlTree)
	{
		// identify the data table key
		$datatable_request_key = $this->base64URLEncode(md5($sqlTree->query));
		
		// process message if it belongs to the same data table
		if($this->input->post(self::_DATATABLE_REQUEST_ID,'') === $datatable_request_key) {
			// extract the action request
			
			switch ( $this->input->post(self::_ACTION_REQUEST_ID,'') ) {
				case 'save': 
					$this->updateQuery($sqlTree);
					$result = $this->selectQuery($sqlTree);
					$this->encoderesult( $result );
					break;
				case 'delete':
					$this->deleteQuery($sqlTree);
					$result = $this->selectQuery($sqlTree);
					$this->encoderesult( $result );
					break;
				case 'create':
					$this->insertQuery($sqlTree);
					$result = $this->selectQuery($sqlTree);
					$this->encoderesult( $result );						
					break;
				default:
					$result = $this->selectQuery($sqlTree);
					$this->encoderesult( $result );
					break;			
			}			
		}		
		else if($this->input->post(self::_DATATABLE_REQUEST_ID,'') === '') {
			// Default, rendering of the data table
			return $this->__dataTableString($datatable_request_key);			
		}
		else {
			return $this->__dataTableString($datatable_request_key);
		}
		
	}
	
	private function postdefault($param, $default) 
	{
		$value = $this->input->post($param,TRUE);
		return isset($value) ? $value : $default;
	}
	
	/**
	 * 
	 * @param unknown $sqlTree
	 */
	private function selectQuery($sqlTree)
	{
		// handle pagination
		$limit = 10;
		$page = intval( $this->postdefault('db~page',0) );
		$sortfield = $this->postdefault('db~sortfield',$sqlTree->column_names[0]);
		$sortorder = $this->postdefault('db~sortorder','desc');
		$offset = $page * $limit;
		$countallresults = 0;
		
		
		// query 
		$this->db->select($sqlTree->column_names); // string|array		
		$this->db->where($sqlTree->where); // string|array
		$this->db->from($sqlTree->table_names); // string
		$this->db->limit($limit, $offset);
		$this->db->order_by($sortfield,$sortorder);
		$query = $this->db->get();
		
		//
		$this->db->select($sqlTree->column_names); // string|array
		$this->db->where($sqlTree->where); // string|array
		$this->db->from($sqlTree->table_names); // string
		$countallresults = $this->db->count_all_results();
		
		// compose the query result
		$columns = array();
		foreach ($query->list_fields() as $field) {
			array_push($columns, (object)array(
				'field'=>$field,
				'headerText'=>ucwords(strtolower(str_replace('_',' ',$field))),
				'sortable'=>'true',
				'iskey'=>$this->isPrimaryKey($sqlTree, $field)				
			) );
		}
		
		// display the query result in json 
		return (object)array(
			'datasource'=>$query->result_array(),
				'count_all_results'=>$countallresults,
					'limit'=>$limit, 'page'=>$page, 'columns'=>$columns);		
	}

	
	/**
	 * 
	 * @param string $table
	 * @param array $pkid
	 */
	private function deleteQuery($sqlTree)
	{
		// extract input array data
		$data = $this->inputarray();
		$keys = array();
		foreach(array_keys($data) as $key){
			if ( $this->startsWith($key, 'key~') === TRUE) {
				$primarykey = str_replace('key~','',$key);
				array_push($keys, $primarykey);
				$this->db->where($primarykey, $data[$key]);
			}
		}

		foreach($sqlTree->table as $table) {
			$this->db->delete($table->name);
		}
	
	}
	
	
	/**
	 * 
	 * @param string $table
	 * @param array $data
	 * @param array $pkid
	 */
	private function updateQuery($sqlTree)
	{
		
		// extract input array data
		$data = $this->inputarray();
		$keys = array();
		foreach(array_keys($data) as $key){
			if ( $this->startsWith($key, 'key~') === TRUE) {
				$primarykey = str_replace('key~','',$key);
				array_push($keys, $primarykey);
				$this->db->where($primarykey, $data[$key]);
			}
		}

		foreach($sqlTree->table as $table) {
			$this->db->update($table->name, $this->getValidColumnNames($table, $data));
		}
	
	}
	
	
	private function insertQuery($sqlTree)
	{
		// extract input array data
		$data = $this->inputarray();
		$keys = array();
		
		foreach($sqlTree->table as $table) {
			$this->db->insert($table->name, $this->getValidColumnNames($table, $data));
		}
		
	}
	
	/**
	 * 
	 * Url base64 encode param string 
	 * 
	 * @param string $input
	 * @return string base64URLEncode
	 */
	private function base64URLEncode($input)
	{	// replace unsafe url characters
		return strtr(base64_encode($input), '+/=', '-_,');
	}
	
	
	/**
	 * 
	 * Url base64 decode param string
	 * 
	 * @param string $input
	 * @return string base64URLDecode
	 */
	private function base64URLDecode($input)
	{	// restore unsafe url characters
		return base64_decode(strtr($input, '-_,', '+/='));
	}
		
	/**
	 * 
	 * Parse the select statement and perform table queries
	 * cache the select statement.
	 * 
	 * @param string $selectStatement
	 * @return array
	 */
	private function selectSqlTreeParser($selectStatement)
	{
		$sqlTree = new stdClass();
		
		$selectStatement = trim($selectStatement); // clear out extra white spaces 

		$sqlTree->column_names = $this->getArrayInRange($selectStatement, 'select', 'from', ',');
		$sqlTree->table_names = $this->getArrayInRange($selectStatement, 'from', 'where', ',');
		$sqlTree->where = $this->getArrayInRange($selectStatement, 'where', ';', 'and');		
				
		$tables = array();
		foreach ($sqlTree->table_names as $tableParsed) {
			$table = new stdClass();
			$table->name = $tableParsed;
			array_push($tables, $table);			
		}				
		$sqlTree->table = $tables;
		
		$sqlTree->query = $selectStatement;
		
		$sqlTree = $this->tableinfoQuery($sqlTree);
		
		return $sqlTree;
	}
	
	/**
	 * table [array] 
	 *   name [string]
	 *   total_columns [int]
	 *   columns [object]
	 *     total_pk [int]
	 *     names [array[string]] array of string column names
	 *     info [array[object]] array of object column
	 *       name [string]
	 *       type [string]
	 *       max_length [int]
	 *       ispk [bool]
	 * table_names [array[string]]
	 * column_names [array[string]]      
	 * where [array[string]]
	 * query [string]
	 */	
	
	private function tableinfoQuery($sqlTree)
	{
		$tables = array();
		$column_names = array();		
	
		foreach($sqlTree->table as $table) {
			$info = new stdClass();
			$table->columns = new stdClass();
			$table->columns->info = $this->db->field_data($table->name);
			$pk_counter = 0;
			foreach ($table->columns->info as $field) {
				array_push($column_names, $field->name);
				if($field->primary_key === 1) {
					$pk_counter++;
				}				
			}
			$table->columns->names = $column_names;
			$table->columns->total_pk = $pk_counter;
			$table->total_columns = sizeof($column_names);
			array_push($tables, $table);			
		}
		$sqlTree->table = $tables;
	
		return $sqlTree;
	
	}
	

	/**
	 *
	 * @param object $sqlTree
	 * @param string $fieldname
	 * @return boolean return TRUE if field name is primary key
	 */
	private function isPrimaryKey($sqlTree, $fieldname)
	{
		foreach ($sqlTree->table as $table) {
			foreach ($table->columns->info as $info) {
				if($info->name == $fieldname && $info->primary_key ===1) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}
	
	private function getValidColumnNames($table, $data)
	{
		$newdata = array();
		foreach(array_keys($data) as $key){
			foreach($table->columns->names as $column_name){
				if($key === $column_name) {
					$newdata[$key] = $data[$key];
				}
			}			
		}
				
		return $newdata; 
	}
	

	
	private function getStringInRange($str, $prefix, $trail)
	{
		// retreive prefix and trail string length
		$plen = strlen($prefix); $tlen = strlen($trail);
		// retreive prefix and trail positions
		$ppos = strripos($str,$prefix); $tpos = strripos($str, $trail);
		if($tpos === FALSE) { $tpos = strlen($str); }
		// extract the string in between
		return substr($str, ($ppos+$plen), $tpos - $ppos - $plen );		
	}
	
	private function getArrayInRange($str, $prefix, $trail, $delimeter)
	{
		// trim the array values
		return array_map('trim',explode(strtolower($delimeter),
			$this->getStringInRange(str_replace($delimeter,
				strtolower($delimeter),$str), $prefix, $trail)));
	}
	
	/**
	 *
	 * Compare haystack starts based on the given needle
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return boolean
	 */
	private function startsWith($haystack, $needle)
	{	// search backwards starting from haystack length characters from the end
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}
	
	/** --------------------------------------------------------------------
	   Static generated content 
	 */

	/**
	 *
	 * @param string $ciref CI reference string
	 * @return string html data table form
	 */
	private function __dataTableString($datatable_request_key)
	{
		return $this->__header() . '<div id="ci-'.random_string('alnum',7).'" class="pui-grid pui-grid-responsive ui-cicrud"><div class="pui-grid-col-12"><form class="ui-cicrud ciform" method="POST"><ul class="ui-menu-bar"><li><a name="ci-crud-create" data-icon="fa-file-o">Create</a></li></ul><div class="ui-controls ui-placeholder"></div><div class="ui-pagination ui-top"></div><div class="ui-datatable"></div><div class="ui-pagination ui-bottom"></div><div class="ui-create-dialog ui-dialog ui-placeholder" title="Control Panel: Create Record"><div>Control Panel: Create Record</div></div><div class="ui-modify-dialog ui-dialog ui-placeholder" title="Control Panel: Modify Record"><div>Control Panel: Modify Record</div></div><input name="' . self::_DATATABLE_REQUEST_ID . '" type="hidden" value="'.$datatable_request_key.'"></form></div></div>';
	}
	
	
	

	
	/**
	 * Generate the minified javascript that will be added in the header
	 *
	 * @return string javascript that is intended to be added in the header
	 */
	public function __header()
	{
		return '<style>form.ui-cicrud{font-size:13px}form.ui-cicrud div.ui-hidden,form.ui-cicrud td.ui-hidden,form.ui-cicrud th.ui-hidden{display:none}form.ui-cicrud div.ui-dialog label{display:inline-block;font-weight:700;width:150px}form.ui-cicrud div.ui-dialog button.ui-yes{margin:0 8px 0 0}form.ui-cicrud div.ui-dialog div.pui-dialog-content.ui-widget-content div{margin:0 0 4px}form.ui-cicrud div.ui-dialog div.pui-dialog-buttonpane.ui-widget-content{padding:0 0 0 12px}form.ui-cicrud div.ui-pagination.pui-paginator{text-align:right}</style><script>var __cicrud=function(){function n(i,n){var t=document.createElement(i);return n&&$.each(n,function(i,n){"text"==i?t.appendChild(document.createTextNode(n)):t.setAttribute(i,n)}),t}function t(i){var e=n(i.typ,i.att);return"undefined"==typeof i.app||null===i.app?e:($.each(i.app,function(i,n){' . 
		'e.appendChild(t(n))}),e)}function e(i,n){"undefined"==typeof n.typ||null===n.typ?$.each(n.app,function(n,e){i.append(t(e))}):i.append(t(n))}function o(i,n){return Math.floor(Math.random()*(n-i+1))+i}function a(i,t,e){var o=$(i.find("div.pui-dialog-content.ui-widget-content")),a=n("div"),u=0;$.each(t,function(i,t){var o=n("div",e[u].iskey===!0?{"class":"ui-hidden"}:{}),d=n("label",{text:e[u].headerText+":"}),c=n("input",e[u].iskey===!0?{name:"key~"+e[u].field,value:t,type:"hidden"}:{name:e[u].field,value:t});o.appendChild(d),o.appendChild(c),a.appendChild(o),u++}),o.empty(),$(a).appendTo(o),$(i.find("input")).puiinputtext(),i.puidialog("show")}function u(i,n,t,a){var u=n.find("div.pui-dialog-content");$.ajax({type:"POST",url:"#",dataType:"json",data:t,beforeSend:function(){n.find("div.pui-dialog-buttonpane").hide(),u.empty(),e(u,' . 
		'{typ:"div",att:{"class":"ui-progressbar"}});var i=n.find("div.ui-progressbar");i.puiprogressbar(),i.puiprogressbar("option","value",o(5,20))},success:function(i){var t=n.find("div.ui-progressbar");t.puiprogressbar("option","value",o(50,80)),setTimeout(function(){t.puiprogressbar("option","value",100),setTimeout(function(){n.puidialog("hide")},200)},300),a(i)},error:function(){n.puidialog("hide")},timeout:function(){n.puidialog("hide")}})}function d(i,t,e){var o=$($(i).find(\'input[name="\'+t+\'"]\'));"hidden"!=o.attr("type")?i.appendChild(n("input",{name:t,type:"hidden",value:e})):o.val(e)}function c(i,n){return d(i,"ci-crud-action",n),console.debug("serialized: "+$(i).serialize()),$(i).serialize()}function r(i,n,t){var e=c(i,"create");u(i,n,e,function(n){s(i,t,n),y(i,n)})}function p(i,n){var t=c(i,"save");u(i,n,t,function(t){s(i,n,t),y' . 
		'(i,t)})}function l(i,n){var t=n.find("div.pui-dialog-content"),o=c(i,"delete");n.find("div.pui-dialog-buttonpane").hide(),t.empty(),e(t,{typ:"div",app:[{typ:"div",app:[{typ:"label",att:{text:"Delete the record?"}}]},{typ:"div",app:[{typ:"button",att:{text:"Yes","class":"ui-yes"}},{typ:"button",att:{text:"No","class":"ui-no"}}]}]}),n.find("button.ui-yes").puibutton({icon:"fa-check",iconPos:"right",click:function(){u(i,n,o,function(t){s(i,n,t)})}}),n.find("button.ui-no").puibutton({icon:"fa-close",iconPos:"right",click:function(){n.puidialog("hide")}})}function f(n){var t=h(n),e=g(n),o=c(n,"");$.ajax({type:"POST",url:"#",dataType:"json",context:$(n),data:o,success:function(o){s(n,t,o),$(n).find(\'a[name="ci-crud-create"]\').click(function(){var n=[];for(i=0;i<o.columns.length;i++)n.push("");a(e,n,o.columns)}),y(n,o)}})}function s(i,n,t)' . 
		'{$.each(t.columns,function(i,n){n.content=function(i){return i[n.field]},n.iskey===!0&&(n.headerStyle="display:none;",n.bodyStyle="display:none;")}),$(i).find("div.ui-datatable").puidatatable({columns:t.columns,responsive:!0,lazy:!0,datasource:function(n,t){null!==t.sortField&&d(i,"db~sortfield",t.sortField),null!==t.sortField&&d(i,"db~sortorder",t.sortOrder>0?"asc":"desc");var e=c(i,"");$.ajax({type:"POST",url:"#",dataType:"json",context:this,data:e,success:function(i){n.call(this,i.datasource)}})},selectionMode:"single",rowSelect:function(i,e){a(n,e,t.columns)}})}function v(i,n,t){return n.puidialog({modal:!0,showEffect:"fade",hideEffect:"fade",width:380,minimizable:!0,maximizable:!0,responsive:!0,beforeShow:function(){n.find("div.pui-dialog-buttonpane").show()},afterHide:function(){setTimeout(function(){$(n.find("div.pui-dialog-content.ui-widget-content"))' . 
		'.empty()},500)},buttons:t}),n}function h(i){var n=$(i).find("div.ui-modify-dialog");return v(i,n,[{text:"Save",icon:"fa-save",iconPos:"right",click:function(){p(i,n)}},{text:"Delete",icon:"fa-trash-o",iconPos:"right",click:function(){l(i,n)}},{text:"Cancel",icon:"fa-remove",iconPos:"right",click:function(){n.puidialog("hide")}}])}function g(i){var n=$(i).find("div.ui-create-dialog"),t=$(i).find("div.ui-modify-dialog");return v(i,n,[{text:"Create",icon:"fa-save",iconPos:"right",click:function(){r(i,n,t)}},{text:"Cancel",icon:"fa-remove",iconPos:"right",click:function(){n.puidialog("hide")}}])}function m(i,n,t){$.each(t,function(t,e){e.puipaginator({totalRecords:n.count_all_results,rows:n.limit,page:n.page,paginate:function(n,t){$(i).find(\'input[name="db~page"]\').val(t.page),f(i)}})})}function y(i,n){m(i,n,[$(i).find("div.ui-pagination.ui-top"),$(i).find("' . 
		' div.ui-pagination.ui-bottom")])}$(function(){$("form.ui-cicrud").each(function(i,t){var e=$(t).find("ul.ui-menu-bar").puimenubar();e.show(),$(t).submit(function(i){return i.preventDefault(),!1}),t.appendChild(n("input",{name:"db~page",type:"hidden",value:"0"})),f(t)})})};__cicrud();</script>';
	}
	
	
	
}


/* End of file Crud.php */
/* Location: ./application/libraries/Crud.php */
