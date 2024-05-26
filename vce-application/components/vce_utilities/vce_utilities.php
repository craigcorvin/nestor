<?php

class VCEUtilities extends Component {

    /**
     * basic info about the component
     */
    public function component_info() {
        return array(
            'name' => 'VCEUtilities',
            'description' => 'Add utility functions to VCE',
            'category' => 'utilities',
            'recipe_fields' => false
        );
    }

    /**
     * things to do when this component is preloaded
     */
    public function preload_component() {

        $content_hook = array(
            'vce_call_add_functions' => 'VCEUtilities::vce_call_add_functions',
        );

        return $content_hook;

    }

    /**
     * add utility functions to VCE
     *
     * @param [VCE] $vce
     */
    public static function vce_call_add_functions($vce) {

        /**
         * returns an ilkyo id, a 14 digit number, for any string
         * like a pheonix from the ashes, ilkyo returns. Viva la ilkyo and special thanks to Mike Min.
         *
         * @param string $string
         * @return string
         */
        $vce->ilkyo = function ($string) {
            // the argument is treated as an integer, and presented as an unsigned decimal number.
            sscanf(crc32($string), "%u", $front);
            // now in reverse
            sscanf(crc32(strrev($string)), "%u", $back);
            // return ilkyo id, which is 14 digits in length
            return $front . substr($back, 0, (14 - strlen($front)));
        };

        /**
         * Dumps JSON object into log file
         *
         * @param string $var
         * @return file_write of print_r(object)
         */
        $vce->log = function ($var, $file = "log.txt") {
            $basepath = defined('INSTANCE_BASEPATH') ? INSTANCE_BASEPATH : BASEPATH;
            file_put_contents($basepath . $file, json_encode($var) . PHP_EOL, FILE_APPEND);
        };

        /**
         * Dumps array in a pre tag with a yellow background
         * Outputs dump of whatever object is specified to the top of the browser window.
         *
         * @param string $var
         * @param string $color
         * @return string of print_r(object)
         */
        $vce->dump = function ($var, $color = 'ffc', $display_backtrace = true) {
        
        	$raw_backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			
			$backtrace = null;
			
			if ($display_backtrace) {
			
				for ($x = 1;$x < 6; $x++) {
				
					if (!empty($raw_backtrace[$x]) && isset($raw_backtrace[$x]['file']) && isset($raw_backtrace[$x]['line'])) {
						$backtrace .= '<div style="font-size:60%">' . $raw_backtrace[$x]['file'] . ' at line ' . $raw_backtrace[$x]['line'] . '</div>';
					} else {
						break;
					}

				}
        	
        	}
        	
            echo '<pre style="background:#' . $color . ';">' . $backtrace . htmlentities(print_r($var, true)) . '</pre>';
        };

    }

}