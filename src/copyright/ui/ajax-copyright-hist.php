<?php
/***********************************************************
 * Copyright (C) 2014-2019 Siemens AG
 * Author: Daniele Fognini, Johannes Najjar, Steffen Weber
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 ***********************************************************/

use Fossology\Lib\Auth\Auth;
use Fossology\Lib\Dao\CopyrightDao;
use Fossology\Lib\Dao\UploadDao;
use Fossology\Lib\Db\DbManager;
use Fossology\Lib\Util\DataTablesUtility;
use Fossology\Agent\Copyright\UI\TextFindingsAjax;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

define("TITLE_COPYRIGHTHISTOGRAMPROCESSPOST", _("Private: Browse post"));

/**
 * @class CopyrightHistogramProcessPost
 * @brief Handles Ajax requests for copyright
 */
class CopyrightHistogramProcessPost extends FO_Plugin
{
  /** @var string $listPage
   * Page slug for plugin
   */
  protected $listPage;
  /** @var string
   * Upload tree to be used
   */
  private $uploadtree_tablename;
  /** @var DbManager
   * DbManager object
   */
  private $dbManager;
  /** @var UploadDao
   * UploadDao object
   */
  private $uploadDao;
  /** @var CopyrightDao
   * CopyrightDao object
   */
  private $copyrightDao;

  /** @var DataTablesUtility
   * DataTablesUtility object
   */
  private $dataTablesUtility;

  /** @var array $textFindingTypes
   * List of types used for text findings
   */
  private $textFindingTypes = ["copyFindings"];

  function __construct()
  {
    $this->Name = "ajax-copyright-hist";
    $this->Title = TITLE_COPYRIGHTHISTOGRAMPROCESSPOST;
    $this->DBaccess = PLUGIN_DB_READ;
    $this->OutputType = 'JSON';
    $this->LoginFlag = 0;
    $this->NoMenu = 0;

    parent::__construct();
    global $container;
    $this->dataTablesUtility = $container->get('utils.data_tables_utility');
    $this->uploadDao = $container->get('dao.upload');
    $this->dbManager = $container->get('db.manager');
    $this->copyrightDao = $container->get('dao.copyright');
  }


  /**
   * @brief Display the loaded menu and plugins.
   * @see FO_Plugin::Output()
   */
  function Output()
  {
    $returnValue = 0;
    if ($this->State != PLUGIN_STATE_READY) {
      return 0;
    }

    $action = GetParm("action", PARM_STRING);
    $upload = GetParm("upload", PARM_INTEGER);
    $type = GetParm("type", PARM_STRING);

    if ($action=="deletedecision" || $action=="undodecision") {
      $decision = GetParm("decision", PARM_INTEGER);
      $pfile = GetParm("pfile", PARM_INTEGER);
    } else if ($action=="deleteHashDecision" || $action=="undoHashDecision") {
      $hash = GetParm("hash", PARM_STRING);
    } else if($action=="update" || $action=="delete" || $action=="undo") {
      $id = GetParm("id", PARM_STRING);
      $getEachID = array_filter(explode(",", trim($id, ',')), function($var) {
          return $var !== "";
      });
      if(count($getEachID) == 4) {
        list($upload, $item, $hash, $type) = $getEachID;
      } else {
        return new Response('bad request while '.$action,
                Response::HTTP_BAD_REQUEST,
                array('Content-type'=>'text/plain')
          );
      }
    }

    /* check upload permissions */
    if (!(($action == "getData" || $action == "getDeactivatedData") &&
        ($this->uploadDao->isAccessible($upload, Auth::getGroupId())) ||
        ($this->uploadDao->isEditable($upload, Auth::getGroupId())))) {
      $permDeniedText = _("Permission Denied");
      $returnValue = "<h2>$permDeniedText</h2>";
    }
    $this->uploadtree_tablename = $this->uploadDao->getUploadtreeTableName(
      $upload);

    if (in_array($type, $this->textFindingTypes) &&
      ($action == "getData" || $action == "getDeactivatedData")) {
      $textFindingsHandler = new TextFindingsAjax($this->uploadtree_tablename);
      if ($action == "getData") {
        $returnValue = $textFindingsHandler->doGetData($type, $upload);
      } elseif ($action == "getDeactivatedData") {
        $returnValue = $textFindingsHandler->doGetData($type, $upload, false);
      }
    } else {
      switch ($action) {
        case "getData":
          $returnValue = $this->doGetData($upload);
          break;
        case "getDeactivatedData":
          $returnValue = $this->doGetData($upload, false);
          break;
        case "update":
          $returnValue = $this->doUpdate($item, $hash, $type);
          break;
        case "delete":
          $returnValue = $this->doDelete($item, $hash, $type);
          break;
        case "undo":
          $returnValue = $this->doUndo($item, $hash, $type);
          break;
        case "deletedecision":
          $returnValue = $this->doDeleteDecision($decision, $pfile, $type);
          break;
        case "undodecision":
          $returnValue = $this->doUndoDecision($decision, $pfile, $type);
          break;
        case "deleteHashDecision":
          $returnValue = $this->doDeleteHashDecision($hash, $upload, $type);
          break;
        case "undoHashDecision":
          $returnValue = $this->doUndoHashDecision($hash, $upload, $type);
          break;
        default:
          $returnValue = "<h2>" . _("Unknown action") . "</h2>";
      }
    }
    return $returnValue;
  }

  /**
   * @brief Handles GET request and create a JSON response
   *
   * Gets the copyright history for given upload and generate
   * a JSONResponse using getTableData()
   * @param int $upload     Upload id to fetch results
   * @param bool $activated True to get activated results, false for disabled
   * @return JsonResponse JSON response for JavaScript
   */
  protected function doGetData($upload, $activated = true)
  {
    $item = GetParm("item", PARM_INTEGER);
    $agent_pk = GetParm("agent", PARM_STRING);
    $type = GetParm("type", PARM_STRING);
    $filter = GetParm("filter", PARM_STRING);
    $listPage = "copyright-list";

    header('Content-type: text/json');
    list($aaData, $iTotalRecords, $iTotalDisplayRecords) = $this->getTableData($upload, $item, $agent_pk, $type,$listPage, $filter, $activated);
    return new JsonResponse(array(
            'sEcho' => intval($_GET['sEcho']),
            'aaData' => $aaData,
            'iTotalRecords' => $iTotalRecords,
            'iTotalDisplayRecords' => $iTotalDisplayRecords
        )
    );
  }

  /**
   * @brief Get the copyright data and fill in expected format
   * @param int     $upload    Upload id to get results from
   * @param int     $item      Upload tree id of the item
   * @param int     $agent_pk  Id of the agent who loaded the results
   * @param string  $type      Type of the statement (statement, url, email, author or ecc)
   * @param string  $listPage  Page slug to use
   * @param string  $filter    Filter data from query
   * @param boolean $activated True to get activated copyrights, else false
   * @return array[][] Array of table data, total records in database, filtered records
   */
  private function getTableData($upload, $item, $agent_pk, $type, $listPage, $filter, $activated = true)
  {
    list ($rows, $iTotalDisplayRecords, $iTotalRecords) = $this->getCopyrights($upload, $item, $this->uploadtree_tablename, $agent_pk, $type, $filter, $activated);
    $aaData = array();
    if (!empty($rows))
    {
      $rw = $this->uploadDao->isEditable($upload, Auth::getGroupId());
      foreach ($rows as $row)
      {
        $aaData [] = $this->fillTableRow($row, $item, $upload, $agent_pk, $type,$listPage, $filter, $activated, $rw);
      }
    }

    return array($aaData, $iTotalRecords, $iTotalDisplayRecords);

  }

  /**
   * @brief Get results from database and format for JSON
   * @param int     $upload_pk           Upload id to get results from
   * @param int     $item                Upload tree id of the item
   * @param string  $uploadTreeTableName Upload tree table to use
   * @param int     $agentId             Id of the agent who loaded the results
   * @param string  $type                Type of the statement (statement, url, email, author or ecc)
   * @param string  $filter              Filter data from query
   * @param boolean $activated           True to get activated copyrights, else false
   * @return array[][] Array of table records, filtered records, total records
   */
  protected function getCopyrights($upload_pk, $item, $uploadTreeTableName, $agentId, $type, $filter, $activated = true)
  {
    $offset = GetParm('iDisplayStart', PARM_INTEGER);
    $limit = GetParm('iDisplayLength', PARM_INTEGER);

    $tableName = $this->getTableName($type);
    $tableNameEvent = $tableName.'_event';
    $orderString = $this->getOrderString();

    list($left, $right) = $this->uploadDao->getLeftAndRight($item, $uploadTreeTableName);

    if ($filter == "")
    {
      $filter = "none";
    }

    $sql_upload = "";
    if ('uploadtree_a' == $uploadTreeTableName)
    {
      $sql_upload = " AND UT.upload_fk=$5 ";
    }

    $join = "";
    $filterQuery = "";
    if ($type == 'statement' && $filter == "nolic")
    {
      $noLicStr = "No_license_found";
      $voidLicStr = "Void";
      $join = " INNER JOIN license_file AS LF on cp.pfile_fk=LF.pfile_fk ";
      $filterQuery = " AND LF.rf_fk IN (SELECT rf_pk FROM license_ref WHERE rf_shortname IN ('$noLicStr','$voidLicStr')) ";
    } else
    {
      // No filter, nothing to do
    }
    $params = array($left, $right, $type, $agentId, $upload_pk);

    $filterParms = $params;
    $searchFilter = $this->addSearchFilter($filterParms);

    $activatedClause = "";
    if ($activated) {
      $activatedClause = "NOT";
    }
    $unorderedQuery = "FROM $tableName AS cp " .
        "INNER JOIN $uploadTreeTableName AS UT ON cp.pfile_fk = UT.pfile_fk " .
        "LEFT JOIN $tableNameEvent AS ce ON ce.".$tableName."_fk = cp.".$tableName."_pk " .
        "AND ce.upload_fk = $5 " .
        $join .
        "WHERE cp.content!='' " .
        "AND ( UT.lft  BETWEEN  $1 AND  $2 ) " .
        "AND cp.type = $3 " .
        "AND cp.agent_fk= $4 " .
        "AND cp." . $tableName . "_pk $activatedClause IN " .
        "(SELECT " . $tableName . "_fk FROM $tableNameEvent WHERE upload_fk = $5 AND is_enabled = false)" .
        $sql_upload;
    $grouping = " GROUP BY mcontent ";

    $countQuery = "SELECT count(*) FROM (
  SELECT mcontent AS content FROM (SELECT
    (CASE WHEN (ce.content IS NULL OR ce.content = '') THEN cp.content ELSE ce.content END) AS mcontent
    $unorderedQuery $filterQuery $grouping) AS k $searchFilter) AS cx";
    $iTotalDisplayRecordsRow = $this->dbManager->getSingleRow($countQuery,
        $filterParms, __METHOD__.$tableName . ".count" . ($activated ? '' : '_deactivated'));
    $iTotalDisplayRecords = $iTotalDisplayRecordsRow['count'];

    $countAllQuery = "SELECT count(*) FROM (SELECT
(CASE WHEN (ce.content IS NULL OR ce.content = '') THEN cp.content ELSE ce.content END) AS mcontent
$unorderedQuery$grouping) as K";
    $iTotalRecordsRow = $this->dbManager->getSingleRow($countAllQuery, $params, __METHOD__,$tableName . "count.all" . ($activated ? '' : '_deactivated'));
    $iTotalRecords = $iTotalRecordsRow['count'];

    $range = "";
    $filterParms[] = $offset;
    $range .= ' OFFSET $' . count($filterParms);
    $filterParms[] = $limit;
    $range .= ' LIMIT $' . count($filterParms);

    $sql = "SELECT mcontent AS content, mhash AS hash, copyright_count FROM (SELECT
(CASE WHEN (ce.content IS NULL OR ce.content = '') THEN cp.content ELSE ce.content END) AS mcontent,
(CASE WHEN (ce.hash IS NULL OR ce.hash = '') THEN cp.hash ELSE ce.hash END) AS mhash,
count(*) AS copyright_count " .
      "$unorderedQuery $filterQuery GROUP BY mcontent, mhash) AS k $searchFilter $orderString $range";
    $statement = __METHOD__ . $filter.$tableName . $uploadTreeTableName . ($activated ? '' : '_deactivated');
    $rows = $this->dbManager->getRows($sql, $filterParms, $statement);

    return array($rows, $iTotalDisplayRecords, $iTotalRecords);
  }

  /**
   * @brief Get table name based on statement type
   *
   * - statement => copyright
   * - ecc       => ecc
   * - others    => author
   * @param string $type Result type
   * @return string Table name
   */
  private function getTableName($type)
  {
    switch ($type) {
      case "ecc" :
        $tableName = "ecc";
        break;
      case "keyword" :
        $tableName = "keyword";
        $filter="none";
        break;
      case "statement" :
        $tableName = "copyright";
        break;
      default:
        $tableName = "author";
    }
    return $tableName;
  }

  /**
   * @brief Create sorting string for database query
   * @return string Sorting string
   */
  private function getOrderString()
  {
    $columnNamesInDatabase = array('copyright_count', 'content');

    $defaultOrder = CopyrightHistogram::returnSortOrder();

    $orderString = $this->dataTablesUtility->getSortingString($_GET, $columnNamesInDatabase, $defaultOrder);

    return $orderString;
  }

  /**
   * @brief Add filter on content
   * @param[out] array $filterParams Parameters list for database query
   * @return string Filter string for query
   */
  private function addSearchFilter(&$filterParams)
  {
    $searchPattern = GetParm('sSearch', PARM_STRING);
    if (empty($searchPattern))
    {
      return '';
    }
    $filterParams[] = "%$searchPattern%";
    return 'WHERE mcontent ilike $'.count($filterParams).' ';
  }

  /**
   * @brief Helper to create action column for results
   * @param string  $hash         Hash of the content
   * @param int     $uploadTreeId Item id in upload tree table
   * @param int     $upload       Upload id
   * @param string  $type         Type of content
   * @param boolean $activated    True if content is activated, else false
   * @param boolean $rw           true if content is editable
   * @return string
   */
  private function getTableRowAction($hash, $uploadTreeId, $upload, $type, $activated = true, $rw = true)
  {
    if($rw)
    {
      $act = "<img";
      if(!$activated)
      {
        $act .= " hidden='true'";
      }
      $act .= " id='delete$type$hash' onClick='delete$type($upload,$uploadTreeId,\"$hash\",\"$type\");' class=\"delete\" src=\"images/space_16.png\">";
      $act .= "<span";
      if($activated) {
        $act .= " hidden='true'";
      }
      $act .= " id='update$type$hash'>deactivated [<a href=\"#\" id='undo$type$hash' onClick='undo$type($upload,$uploadTreeId,\"$hash\",\"$type\");return false;'>Undo</a>]</span>";
      return $act;
    }
    if(!$activated) {
      return "deactivated";
    }
    return "";
  }

  /**
   * @brief Fill table content for JSON response
   * @param array   $row          Result row from database
   * @param int     $uploadTreeId Upload tree id of the item
   * @param int     $upload       Upload id
   * @param int     $agentId      Agent id
   * @param string  $type         Type of content
   * @param string  $listPage     Page slug
   * @param string  $filter       Filter for query
   * @param boolean $activated    True to get activated results, false otherwise
   * @return string[]
   * @internal param boolean $normalizeString
   */
  private function fillTableRow($row, $uploadTreeId, $upload, $agentId, $type,$listPage, $filter = "", $activated = true, $rw = true)
  {
    $hash = $row['hash'];
    $output = array('DT_RowId' => "$upload,$uploadTreeId,$hash,$type" );

    $link = "<a href='";
    $link .= Traceback_uri();
    $urlArgs = "?mod=".$listPage."&agent=$agentId&item=$uploadTreeId&hash=$hash&type=$type";
    if (!empty($filter)) {
      $urlArgs .= "&filter=$filter";
    }
    $link .= $urlArgs . "'>" . $row['copyright_count'] . "</a>";
    $output['0'] = $link;
    $output['1'] = convertToUTF8($row['content']);
    $output['2'] = $this->getTableRowAction($hash, $uploadTreeId, $upload, $type, $activated, $rw);
    if($rw && $activated)
    {
      $output['3'] = "<input type='checkbox' class='deleteBySelect$type' id='deleteBySelect$type$hash' value='".$upload.",".$uploadTreeId.",".$hash.",".$type."'>";
    }
    else
    {
        $output['3'] = "";
    }
    return $output;
  }

  /**
   * @brief Update result
   * @param int    $itemId Upload tree id of the item to update
   * @param string $hash   Hash of the content
   * @param string $type   'copyright'|'ecc'|'keyword'
   * @return Response
   */
  protected function doUpdate($itemId, $hash, $type)
  {
    $content = GetParm("value", PARM_RAW);
    if (!$content)
    {
      return new Response('empty content not allowed', Response::HTTP_BAD_REQUEST ,array('Content-type'=>'text/plain'));
    }

    $item = $this->uploadDao->getItemTreeBounds($itemId, $this->uploadtree_tablename);
    $cpTable = $this->getTableName($type);
    $this->copyrightDao->updateTable($item, $hash, $content, Auth::getUserId(), $cpTable);

    return new Response('success', Response::HTTP_OK,array('Content-type'=>'text/plain'));
  }

  /**
   * @brief Disable a result
   * @param int    $itemId Upload tree id of the item to update
   * @param string $hash   Hash of the content
   * @param string $type   'copyright'|'ecc'
   * @return Response
   */
  protected function doDelete($itemId, $hash, $type)
  {
    $item = $this->uploadDao->getItemTreeBounds($itemId, $this->uploadtree_tablename);
    $cpTable = $this->getTableName($type);
    $this->copyrightDao->updateTable($item, $hash, '', Auth::getUserId(), $cpTable, 'delete');
    return new Response('Successfully deleted', Response::HTTP_OK, array('Content-type'=>'text/plain'));
  }

  /**
   * @brief Rollback a result
   * @param int    $itemId Upload tree id of the item to update
   * @param string $hash   Hash of the content
   * @param string $type   'copyright'|'ecc'
   * @return Response
   */
  protected function doUndo($itemId, $hash, $type) {
    $item = $this->uploadDao->getItemTreeBounds($itemId, $this->uploadtree_tablename);
    $cpTable = $this->getTableName($type);
    $this->copyrightDao->updateTable($item, $hash, '', Auth::getUserId(), $cpTable, 'rollback');
    return new Response('Successfully restored', Response::HTTP_OK, array('Content-type'=>'text/plain'));
  }

  /**
   * @brief Disable a decision
   * @param int    $decisionId Decision id
   * @param string $pfileId    Pfile id of the item
   * @param string $type       'copyright'|'ecc'
   * @return JsonResponse
   */
  protected function doDeleteDecision($decisionId, $pfileId, $type) {
    $this->copyrightDao->removeDecision($type."_decision", $pfileId, $decisionId);
    return new JsonResponse(array("msg" => $decisionId . " .. " . $pfileId  . " .. " . $type));
  }

  /**
   * @brief Rollback a decision
   * @param int    $decisionId Decision id
   * @param string $pfileId    Pfile id of the item
   * @param string $type       'copyright'|'ecc'
   * @return JsonResponse
   */
  protected function doUndoDecision($decisionId, $pfileId, $type) {
    $this->copyrightDao->undoDecision($type."_decision", $pfileId, $decisionId);
    return new JsonResponse(array("msg" => $decisionId . " .. " . $pfileId  . " .. " . $type));
  }

  /**
   * @brief Disable decisions for an upload which matches a hash
   * @param string $hash   Hash of the decision
   * @param int    $upload Upload id
   * @param string $type   'copyright'|'ecc'
   * @return JsonResponse
   */
  protected function doDeleteHashDecision($hash, $upload, $type)
  {
    $tableName = $type."_decision";
    $decisions = $this->copyrightDao->getDecisionsFromHash($tableName, $hash,
      $upload, $this->uploadtree_tablename);
    foreach ($decisions as $decision) {
      $this->copyrightDao->removeDecision($tableName, $decision['pfile_fk'],
        $decision[$tableName . '_pk']);
    }
    return new JsonResponse(array("msg" => "$hash .. $upload .. $type"));
  }

  /**
   * @brief Rollback decisions for an upload which matches a hash
   * @param string $hash   Hash of the decision
   * @param int    $upload Upload id
   * @param string $type   'copyright'|'ecc'
   * @return JsonResponse
   */
  protected function doUndoHashDecision($hash, $upload, $type)
  {
    $tableName = $type."_decision";
    $decisions = $this->copyrightDao->getDecisionsFromHash($tableName, $hash,
      $upload, $this->uploadtree_tablename);
    foreach ($decisions as $decision) {
      $this->copyrightDao->undoDecision($tableName, $decision['pfile_fk'],
        $decision[$tableName . '_pk']);
    }
    return new JsonResponse(array("msg" => "$hash .. $upload .. $type"));
  }
}

$NewPlugin = new CopyrightHistogramProcessPost();
$NewPlugin->Initialize();
