<?php

class toiletClass {

    var $config;
    var $port;
    var $data;

    public function getData() {
        try {
            $this->config = @parse_ini_file("./status.ini");
        }
        catch (Exception $e) {
            $this->answer(array(
                'sensor_status' => false,
                'reason' => 'Can`t parse config'
            ));
        }

        $this->port = (isset($_GET["port"]) && is_numeric($_GET["port"])) ? $_GET["port"]:2;

        $this->data = exec("cat /proc/adc".$this->port);
        $this->data = str_replace("adc$this->port:",'',$this->data);

        if (!is_numeric($this->data) || !$this->config) {
            $this->answer(array(
                'sensor_status' => false
            ));
        }
    }

    public function sendData() {
        $l_status = ($this->data > 3400) ? false:true;
        if ($this->config['status'] == $l_status) {
            $l_change = $this->config['time'];
        } else {
            $time = time();
            $this->write_ini_file(array(
                'light' => array(
                    'status' => $l_status,
                    'time' => $time
                )
            ),'./status.ini',true);
            $l_change = $time;
        }

        $this->answer(array(
            'light_value' => (int)$this->data,
            'light_status' => (bool)$l_status,
            'light_change' => (int)$l_change,
            'sensor_status' => true
        ));
    }

    private function answer($result) {
        header('Content-Type: application/json');
        echo json_encode($result);
        die();
    }

    private function write_ini_file($assoc_arr, $path, $has_sections=FALSE) {
        $content = "";
        if ($has_sections) {
            foreach ($assoc_arr as $key=>$elem) {
                $content .= "[".$key."]\n";
                foreach ($elem as $key2=>$elem2) {
                    if(is_array($elem2))
                    {
                        for($i=0;$i<count($elem2);$i++)
                        {
                            $content .= $key2."[] = \"".$elem2[$i]."\"\n";
                        }
                    }
                    else if($elem2=="") $content .= $key2." = \n";
                    else $content .= $key2." = \"".$elem2."\"\n";
                }
            }
        }
        else {
            foreach ($assoc_arr as $key=>$elem) {
                if(is_array($elem))
                {
                    for($i=0;$i<count($elem);$i++)
                    {
                        $content .= $key2."[] = \"".$elem[$i]."\"\n";
                    }
                }
                else if($elem=="") $content .= $key2." = \n";
                else $content .= $key2." = \"".$elem."\"\n";
            }
        }

        if (!$handle = fopen($path, 'w')) {
            return false;
        }
        if (!fwrite($handle, $content)) {
            return false;
        }
        fclose($handle);
        return true;
    }

}


$cls = new toiletClass();
$cls->getData();
$cls->sendData();











