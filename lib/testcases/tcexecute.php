<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 * This script is distributed under the GNU General Public License 2 or later. 
 *
 * Filename $RCSfile: tcexecute.php,v $
 * @version $Revision: 1.4 $
 *
 * Handles testcase execution through AJAX calls. 
 * Testcases are executed on a remote server, and the response 
 * is sent back via an XML-RPC server.
 *
 * Code contributed by: 
 *
 * Importnat note:
 * XML-RPC Server Settings need to be configured using the custom fields feature.
 * Three fields each for testcase level and testsuite level are required. 
 * The fields are: server_host, server_port and server_path. 
 *                 Precede 'tc_' for custom fields assigned to testcase level.
 * 
 *
 * @modified $Date: 2007/11/19 21:33:54 $ by $Author: asielb $
*/
require_once("../../config.inc.php");
require_once("common.php");
require_once("csv.inc.php");
require_once("xml.inc.php");
require_once("../keywords/keywords.inc.php");
require_once("../../third_party/phpxmlrpc/lib/xmlrpc.inc");
require_once("../../third_party/phpxmlrpc/lib/xmlrpcs.inc");
require_once("../../third_party/phpxmlrpc/lib/xmlrpc_wrappers.inc");
testlinkInitPage($db);

$tcase_id = isset($_REQUEST['testcase_id']) ? intval($_REQUEST['testcase_id']) : 0;


$executionResults = array();
$xmlResponse=null;

$msg=array();
$msg['check_server_setting']="<tr><td>" . lang_get("check_test_automation_server") . "</td></tr>";

$item_type=$_GET['level'];

switch($item_type)
{
  case "testcase":
  $xmlResponse=remote_exec_testcase($db,$tcase_id,$msg);
  break;  

  case "testsuite":
  case "testproject":
  $tcase_parent_id=$_REQUEST[$item_type . "_id"];
  $xmlResponse=remote_exec_testcase_set($db,$tcase_parent_id,$msg);
  break;
  
  
  default:
  echo "<b>" . lang_get("service_not_supported") . "</b>";
  break;
}

if( !is_null($xmlResponse) )
{
  $xmlResponse = '<table width="95%" class="simple" border="0">' . $xmlResponse .
	               '</table>';
	echo $xmlResponse;
}

?>

<?php
/*
  function: 

  args :
  
  returns: 

*/
function remote_exec_testcase(&$db,$tcase_id,$msg)
{
  $cfield_manager = new cfield_mgr($db);
  $tree_manager = new tree($db);
  $xmlResponse=null;
  $executionResults=array();
	
	$executionResults[$tcase_id] =  executeTestCase($tcase_id,$tree_manager,$cfield_manager);

	$myResult = $executionResults[$tcase_id]['result'];
	$myNotes = $executionResults[$tcase_id]['notes'];
	$myMessage = $executionResults[$tcase_id]['message'];
	
	$xmlResponse = '<tr><th colspan="2">' . lang_get('result_after_exec') . " {$myMessage}</th></tr>";

	if($myResult != -1 and $myNotes != -1){
		$xmlResponse .= "<tr><td>" . lang_get('tcexec_result') . "</td>" . 
		                "<td>{$myResult}</td></tr>" . 
		                "<tr><td>" . lang_get('tcexec_notes'). "</td>" . 
		                "<td> {$myNotes.}</td></tr>";
	}
	else{
		$xmlResponse .= $msg['check_server_setting'];	
	}
  return $xmlResponse;
} // function end


/*
  function: 

  args :
  
  returns: 

*/
function remote_exec_testcase_set(&$db,$parent_id,$msg)
{
  $cfield_manager = new cfield_mgr($db);
  $tree_manager = new tree($db);
	$xmlResponse=null;
	$executionResults=array();
	$node_type=$tree_manager->get_available_node_types();
	
	$subtree_list = $tree_manager->get_subtree($parent_id);
	
	foreach($subtree_list as $_key => $_value){
		if (is_array($_value)){
			if($_value['node_type_id'] == $node_type['testcase']) {
				$executionResults[$_value['id']] = executeTestCase($_value['id'],$tree_manager,$cfield_manager);
			}
			else{
				//Can add some logic here. If required.
				continue;
			}
		}
	}
	if($executionResults){
		foreach($executionResults as $key => $value){
		  
		  $node_info=$tree_manager->get_node_hierachy_info($key);
		  
			$xmlResponse .= '<tr><th colspan="2">' . lang_get('tcexec_results_for') .
			                $node_info['name'] . "</th></tr>";
			$serverTest = 1;
			foreach($value as $_key => $_value){
				if($_value != -1){
					$xmlResponse .= "<tr><td>" . $_key . ":</td><td>" . $_value . "</td></tr>";
				}
				else
					$serverTest = $serverTest+1;
				
			}
			if($serverTest != 1){
				$xmlResponse .= $xmlResponse .= $msg['check_server_setting'];
			}
		}
	}
  return $xmlResponse;
} // function end
?>