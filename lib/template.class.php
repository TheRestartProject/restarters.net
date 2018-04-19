<?php

    class Template {

        protected $variables = array();
        protected $_controller;
        protected $_action;

        /** array to exclude certain routes (like csv exports or AJAX urls) **/
        private $exclude = array(
                            'restarters_in_group',
                            'group_locations',
                            'party_data',
                            'category_list',
                            'restarters',
                            'stats',
                            'info',
                            'deleteimage',
                            'ajax_update',
                            'ajax_update_save',
                            'delete_device_image' // devices modal update
                            );

        function __construct($controller,$action) {
            $this->_controller = $controller;
            $this->_action = $action;
        }

        /** Set Variables **/
        function set($name,$value) {
            $this->variables[$name] = $value;
        }

        /** Display Template **/
        function render() {
            extract($this->variables);

            if(!in_array($this->_action, $this->exclude) && !in_array($this->_controller, array('rss', 'export','outbound', 'api'))){
                /* Include Base Head @ view/head.php */
                include (ROOT . DS . 'app' . DS . 'view' . DS . 'head.php');



                if (file_exists(ROOT . DS . 'app' . DS . 'view' . DS . $this->_controller . DS . 'header.php')) {
                    include (ROOT . DS . 'app' . DS . 'view' . DS . $this->_controller . DS . 'header.php');
                } else {
                    include (ROOT . DS . 'app' . DS . 'view' . DS . 'header.php');
                }

                include (ROOT . DS . 'app' . DS . 'view' . DS . $this->_controller . DS . $this->_action . '.php');

                if (file_exists(ROOT . DS . 'app' . DS . 'view' . DS . $this->_controller . DS . 'footer.php')) {
                    include (ROOT . DS . 'app' . DS . 'view' . DS . $this->_controller . DS . 'footer.php');
                } else {
                    include (ROOT . DS . 'app' . DS . 'view' . DS . 'footer.php');
                }

                /* Include Base Foot @ view/foot.php */
                include (ROOT . DS . 'app' . DS . 'view' . DS . 'foot.php');
            }

            elseif($this->_controller == 'rss'){
                /** Manage RSS Feeds (used for public site widgets/WordPress Implementations) **/
                include (ROOT . DS . 'app' . DS . 'view' . DS . 'feeds' . DS . $this->_action . '.php');
            }
            elseif($this->_controller == 'export'){
                /** set headers for data exports **/
                if($this->_action == 'devices'){
                  $filename = 'restartproject_devices_' . date('d-m-Y', time()) . '.csv';
                }
                elseif($this->_action == 'parties') {
                  $filename = 'restartproject_parties_' . date('d-m-Y', time()) . '.csv';
                }
                header('Content-Type: application/csv;charset=UTF-8');
                header('Content-Disposition: attachment; filename="'.$filename.'";');

                include (ROOT . DS . 'app' . DS . 'view' . DS . 'export' . DS . $this->_action . '.php');

            }
            elseif($this->_action == 'stats' || $this->_controller == 'outbound'){
                /** Manage Stat Pages for iframe embedding in third party sites **/
                include (ROOT . DS . 'app' . DS . 'view' . DS . 'head.php');
                include (ROOT . DS . 'app' . DS . 'view' . DS . $this->_controller . DS . $this->_action . '.php');
                include (ROOT . DS . 'app' . DS . 'view' . DS . 'foot.php');
            }
            else {
                /** requested file should not include header and footer, might be different mime or AJAX content **/
                if(file_exists(ROOT . DS . 'app' . DS . 'view' . DS . $this->_controller . DS . $this->_action . '.php')){
                    include (ROOT . DS . 'app' . DS . 'view' . DS . $this->_controller . DS . $this->_action . '.php');
                }
            }
        }
    }
