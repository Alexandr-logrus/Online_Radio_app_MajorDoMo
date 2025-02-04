<?php

/**
 * Online Radio Application
 *
 * module for MajorDoMo project
 * @author Fedorov Ivan <4fedorov@gmail.com>
 * @copyright Fedorov I.A.
 * @version 1.3.1 October 2014
 */

class app_radio extends module
{

    /**
     * radio
     *
     * Module class constructor
     *
     * @access private
     */
    function app_radio()
    {
        $this->name = "app_radio";
        $this->title = "Internet Radio";
        $this->module_category = "<#LANG_SECTION_APPLICATIONS#>";
        $this->checkInstalled();
    }

    /**
     * saveParams
     *
     * Saving module parameters
     *
     * @access public
     */
    function saveParams($data = 1)
    {
        $p = array();
        if (isset($this->id)) {
            $p["id"] = $this->id;
        }
        if (isset($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (isset($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (isset($this->tab)) {
            $p["tab"] = $this->tab;
        }
        return parent::saveParams($p);
    }

     /**
     * getParams
     *
     * Getting module parameters from query string
     *
     * @access public
     */
    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $tab;
        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode = $edit_mode;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        }
    }

    /**
     * Run
     *
     * Description
     *
     * @access public
     */
    function run()
    {
        global $session;
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (isset($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (isset($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        if ($this->single_rec) {
            $out['SINGLE_REC'] = 1;
        }
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'app_radio' || $this->data_source == '') {

            $out['VER'] = '1.3.2';
            global $select_terminal;
            if ($select_terminal != '')
                sg('RadioSetting.PlayTerminal', $select_terminal);

            $out['PLAY_TERMINAL'] = getGlobal('RadioSetting.PlayTerminal');
            $res = SQLSelect("SELECT NAME FROM terminals");
            if ($res[0]) {
                $out['LIST_TERMINAL'] = $res;
            }

            if ($this->view_mode == '' || $this->view_mode == 'view_stations') {
                $this->view_stations($out);
            }
            if ($this->view_mode == 'clear_stations') {
                $this->clear_stations();
                $this->redirect("?");
            }
            if ($this->view_mode == 'import_stations') {
                $this->import_stations($out);
            }
            if ($this->view_mode == 'export_stations') {
                $this->export_stations($out);
            }
            if ($this->view_mode == 'edit_stations') {
                $this->edit_stations($out, $this->id);
            }
            if ($this->view_mode == 'delete_stations') {
                $page = gr('page');
                $this->delete_stations($this->id);
                $this->redirect("?md=panel&action=app_radio&page=" . $page);
            }
        }
    }

    /**
     * FrontEnd
     *
     * Module frontend
     *
     * @access public
     */
    function usual(&$out)
    {
        $out['notpaging'] = 1;
        $this->view_stations($out);

        $current_volume = gg('RadioSetting.VolumeLevel');
        $last_stationID = gg('RadioSetting.LastStationID');
        $out['VOLUME'] = $current_volume;

        if ($last_stationID) {
            $total = count($out['RESULT']);
            for ($i = 0; $i < $total; $i++) {
                if ($last_stationID == $out['RESULT'][$i]['ID']) {
                    $out['RESULT'][$i]['SELECT'] = 1;
                    break;
                }
            }
        } else {
            $out['RESULT'][0]['SELECT'] = 1;
        }

        global $ajax;
        if ($ajax != '') {
            global $cmd;
            if ($cmd != '') {
                if (!$this->intCall) {
                    echo $cmd . ' ';
                }
                global $s_id;
                if ($s_id != '') {
                    $total = count($out['RESULT']);
                    for ($i = 0; $i < $total; $i++) {
                        if ($s_id == $out['RESULT'][$i]['ID']) {
                            $out['PLAY'] = trim($out['RESULT'][$i]['stations']);
                            $last_stationID = $out['RESULT'][$i]['ID'];
                            $LastStationName = $out['RESULT'][$i]['name'];
                            sg('RadioSetting.LastStationID', $last_stationID);
                            sg('RadioSetting.LastStationName', $LastStationName);
                            break;
                        }
                    }
                } else {
                    if ($out['RESULT'][0]['ID']) {
                        $out['PLAY'] = trim($out['RESULT'][0]['stations']);
                        $last_stationID = $out['RESULT'][0]['ID'];
                        sg('RadioSetting.LastStationID', $last_stationID);
                    }
                }
                global $volume;
                if ($volume != '') {
                    sg('RadioSetting.VolumeLevel', $volume);
                }
		global $play_terminal;
		if ($play_terminal != '') {
		    sg('RadioSetting.PlayTerminal', $play_terminal);
		}
                $this->select_player($out);
            }

            if (!$this->intCall) {
                echo "OK";
                if ($res) {
                    echo $res;
                }
                exit;
            }
        }
    }

	function change_station($val) {
		if (is_numeric($val)) {
			$res = SQLSelect("SELECT * FROM app_radio WHERE ID='$val'");
		} else {
			$res = SQLSelect("SELECT * FROM app_radio WHERE name='$val'");
		}

		if ($res[0]['ID']) {
			sg('RadioSetting.LastStationID', $res[0]['ID']);
			sg('RadioSetting.LastStationName', $res[0]['name']);
			$this->control('st_change');
		} else {
			//$log = getLogger($this);
			//$log->error('Станции ' . $val . ' не найдено!');
		}
	}

	function set_volume($vol) {
		global $volume;
		$volume = $vol;
		$this->control('vol');
	}

	function control($state) {
		$out = array();
		global $cmd;
		$cmd = $state;
		//echo('control->'.$cmd);
		if ($cmd == 'st_change') {
			if (gg('RadioSetting.On'))
				$cmd = 'play';
		}
		if ($cmd == 'play') {
			$last_stationID = getGlobal('RadioSetting.LastStationID');
			$res = SQLSelect('SELECT `stations` FROM `app_radio` WHERE `ID` = ' . intval($last_stationID));
			if ($res[0]['stations']) {
				$out['PLAY'] = $res[0]['stations'];
			} else {
				$res = SQLSelect('SELECT `stations` FROM `app_radio`');
				if($res[0]['stations']) {
					$out['PLAY'] = $res[0]['stations'];
				} else {
					say('Станций не найдено');
					$out['PLAY'] = 'http://listen.shoutcast.com/europarussia';
				}
			}
		}
		$this->select_player($out);
	}

    function select_player(&$out){
        global $cmd;
        global $volume;

        $play_terminal = gg('RadioSetting.PlayTerminal');
        if ($cmd == 'play'){
            sg('RadioSetting.On', 1);
            if (preg_match('/101.ru\/api\/channel\/getServers/is', $out['PLAY'])) {
                //DebMes($out['PLAY']);
                $json = file_get_contents($out['PLAY']);
                $array = json_decode($json, true);
                $out['PLAY'] = $array['result'][0]['urlStream'];
                //DebMes($out['PLAY']);
            }
            playMedia($out['PLAY'], $play_terminal);
        } elseif ($cmd == 'stop') {
            sg('RadioSetting.On', 0);
            stopMedia($play_terminal);
        } elseif ($cmd == 'vol') {
            sg('RadioSetting.VolumeLevel', $volume);
            setPlayerVolume($play_terminal, $volume);
        }
    }

    function view_stations(&$out)
    {
        $table_name = 'app_radio';
        $res = SQLSelect("SELECT * FROM $table_name ORDER BY FAVORITE DESC, name");
        if ($res[0]['ID']) {
            if (!$out['notpaging']) {
                paging($res, 50, $out); // search result paging
            }
            $out['RESULT'] = $res;
            $total = count($out['RESULT']);
            for ($i = 0; $i < $total; $i++) {
                $out['RESULT'][$i]['position'] = $i + 1;
                $out['RESULT'][$i]['page'] = $out['CURRENT_PAGE'];
            }
        }
    }

    function edit_stations(&$out, $id)
    {
        $table_name = 'app_radio';
        $rec = SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");

        if ($this->mode == 'update') {
            $ok = 1;
            $rec['stations'] = gr('stations');
            $rec['name'] = gr('name');
            $rec['FAVORITE'] = (int)gr('favorite');
            if ($rec['stations'] == '' || $rec['name'] == '') {
                $out['ERR_stations'] = 1;
                $ok = 0;
            }
            //UPDATING RECORD
            if ($ok) {
                if ($rec['ID']) {
                    SQLUpdate($table_name, $rec); //update
                } else {
                    $new_rec = 1;
                    $rec['ID'] = SQLInsert($table_name, $rec); //adding new record
                }
                $out['OK'] = 1;
            } else {
                $out['ERR'] = 1;
            }
        }
        outHash($rec, $out);
    }

    function import_stations(&$out)
    {
        $table_name = 'app_radio';
        if ($this->mode == 'update') {
            global $file;
            if (file_exists($file)) {
                $data = LoadFile($file);
                $data = str_replace("\r", '', $data);
                $data = str_replace("\n\n", "\n", $data);
                $lines = mb_split("\n", $data);
                $total = count($lines);
                for ($i = 0; $i < $total; $i++) {
                    $rec = array();
                    $rec_ok = 1;
                    list($rec['name'], $rec['stations'], $rec['FAVORITE']) = explode(";", $lines[$i]);
                    if ($rec['stations'] == '') {
                        $rec_ok = 0;
                    }
                    if ($rec_ok) {
                        $old = SQLSelectOne("SELECT ID FROM " . $table_name . " WHERE stations '" . DBSafe($rec['stations']) . "'");// LIKE
                        if ($old['ID']) {
                            $rec['ID'] = $old['ID'];
                            SQLUpdate($table_name, $rec);
                        } else {
                            SQLInsert($table_name, $rec);
                        }
                        $out["TOTAL"]++;
                    }
                }
            } else {
                $out['ERR'] = 1;
            }
        }
    }

    function export_stations(&$out) {
        $data = '';
        $res = SQLSelect('SELECT name, stations, FAVORITE FROM app_radio ORDER BY FAVORITE DESC, name');
        foreach ($res as $item) {
                $data .= $item['name'] . ';' . $item['stations'] . ';' . $item['FAVORITE'] . PHP_EOL;
        }
        header('Content-Disposition: attachment; filename=app_radio_export_' . date('d-m-Y_H-i-s') . '.txt');
        header('Content-Type: text/plain');
        die ($data);
    }

    function delete_stations($id)
    {

        $table_name = 'app_radio';
        $rec = SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
        SQLExec("DELETE FROM $table_name WHERE ID='" . $rec['ID'] . "'");
    }

    function clear_stations()
    {
        $table_name = 'app_radio';
        SQLExec("TRUNCATE TABLE $table_name");
        SQLExec("ALTER TABLE $table_name AUTO_INCREMENT=0");
    }

    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($parent_name = "")
    {
        $className = 'Radio';
        $objectName = 'RadioSetting';
        $metodName = 'Control';
        $properties = array('LastStationID', 'VolumeLevel', 'PlayTerminal', 'On');
        $code = 'include_once(DIR_MODULES.\'app_radio/app_radio.class.php\');
$app_radio = new app_radio();

if (is_array($params)) {
    foreach ($params as $key=>$value) {
        switch((string)$key) {
            case \'sta\': $app_radio->change_station($params[\'sta\'], $app_radio); break;
            case \'cmd\': $app_radio->control($params[\'cmd\']); break;
            case \'vol\': $app_radio->set_volume($params[\'vol\'], $app_radio); break;
            default:
                if($value == \'play\' || $value == \'stop\') $app_radio->control($value);
                elseif(strpos($value, \'vol\') === 0) $app_radio->set_volume((int)substr($value, 3), $app_radio);
                elseif(strpos($value, \'sta:\') === 0) $app_radio->change_station(substr($value, 4), $app_radio);
        }
    }
}';

		// Class
		$class_id = addClass($className);
		if ($class_id) {
			$class = SQLSelectOne('SELECT * FROM `classes` WHERE `ID` = '.$class_id);
			$class['DESCRIPTION'] = 'Онлайн радио';
			SQLUpdate('classes', $class);
		}

		// Method
		$meth_id = addClassMethod($className, $metodName, '');

		// Object
		$object_id = addClassObject($className, $objectName);
		if ($object_id) {
			$object = SQLSelectOne('SELECT * FROM `objects` WHERE `ID` = '.$object_id);
			$object['DESCRIPTION'] = 'Настройки';
			SQLUpdate('objects', $object);
		}

		// Properties
		foreach ($properties as $title) {
			$properti = SQLSelectOne('SELECT `ID` FROM `properties` WHERE `OBJECT_ID` = '.$object_id.' AND `TITLE` LIKE \''.DBSafe($title).'\'');
			if (!$properti) {
				$properti = array();
				$properti['TITLE'] = $title;
				$properti['OBJECT_ID'] = $object_id;
				$properti_id = SQLInsert('properties', $properti);
			}
		}

		// Code
		if ($meth_id) {
			injectObjectMethodCode($objectName.'.'.$metodName, $this->name, $code);
		}

        parent::install($parent_name);
    }

	function uninstall()
	{
		SQLExec("drop table if exists app_radio");
		parent::uninstall();
	}

    function dbInstall($data)
    {

$data = <<<EOD
 app_radio: ID int(10) unsigned NOT NULL auto_increment
 app_radio: stations text
 app_radio: name text
 app_radio: FAVORITE tinyint(1) unsigned
EOD;
        parent::dbInstall($data);
    }
// --------------------------------------------------------------------
}
?>
