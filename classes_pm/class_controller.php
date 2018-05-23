<?php 
    
    class pure
    {
        
        public $model;
        
        function __construct() 
        {
            $this->model = new model;
        }
        
        public function checkPage() 
        {
            if ($_POST) 
            {
                $allowed_methods = array (
                    'add_content_block' => 'addContentBlock',
                    'increase_priority' => 'increasePriority',
                    'decrease_priority' => 'decreasePriority',
                    'add_new_element' => 'addNewElement',
                    'add_leaves' => 'addLeaves',
                    'delete_element' => 'deleteElement',
                    'edit_element' => 'editElement',
                    'add_other_option' => 'addOtherOption',
                    'add_css_option' => 'addCssOption',
                    'delete_other_option' => 'deleteOtherOption',
                    'delete_css_option' => 'deleteCssOption',
                    'fav_element' => 'favElement'
                );
                
                // check method:
                if (method_exists($this, $allowed_methods[$_POST['action']])) {
                    // if exists, use it:
                    $this->$allowed_methods[$_POST['action']]($_POST);
                } else {
                    // if method don't exist, redirect to main url:
                    $redirect_to = CONFIGURATION::MAIN_URL;
                    header ("Location: $redirect_to");
                    exit();
                }
            }
            
            if (isset($_GET['page'])) {
                // check if such page is specified in settings:
                if ($this->checkPagesArray($_GET['page']) == true ) {
                    // if so, I check if the file exists: 
                    if (file_exists('view/'.$_GET['page'].'.php')) {
                        // and plug it:
                        return $_GET['page'];
                    } else {
                        // if file does not exist, show error 404:
                        return '404';
                    }
                } else {
                    // if no, show 404 error:
                    return '404';
                }
            } else {
                // show the main page:
                return 'main_page';
            }
        }
        
        public function getPage() 
        {
            
        }
        
        public function checkPagesArray($string)
        {            
            // check if such page is specified in settings:
            if (array_key_exists($_GET['page'],CONFIGURATION::ALL_PAGES) 
                OR  array_key_exists($_GET['page'],CONFIGURATION::SUB_PAGES)) {
                return true;
            } else {
                return false;
            }
        }
                
        public function generatePageTitle() 
        {
            // take page url:
            $page_url = $this->checkPage();
                
            // if page not exist, show error:
            if ($page_url == '404') {
                return 'Error 404';
            } elseif ($page_url == 'main_page') { 
                // if it's main page, we don't need title:
                return null;
            } elseif ($this->checkPagesArray($page_url) == true) {
                // else, check if such page is specified in settings:
                return CONFIGURATION::ALL_PAGES[$page_url];
            } else {
                return null;
            }                
        }
        
        public function generateSubMenu($parent) 
        {
            $sub_pages = array();
            
            // check if such parent page have subpages:
            foreach (CONFIGURATION::SUB_PAGES AS $get => $params) {
                // if so, get them:
                if (in_array($parent,$params)) {
                    $sub_pages += [$get => $params[0]];
                }
            }
            return $sub_pages;
        }

        public function fish($max) 
        {
            return substr(configuration::FISH,0,$max);
        }
        
        public function getAllProjects() 
        {
            return $this->model->getAllProjects();
        }
 
        public function getAllSubProjects($projectId) 
        {
            return $this->model->getAllSubProjects($projectId);
        }
 
        public function createDocumentTree($array, $str = NULL) 
        {
            $styles = '';
            // TODO: how to build div, buttons, forms, sliders and other?
            foreach($array as $outer => $inner) {
                // if there is some object in div:
                if (is_array($inner)){
                    // get parent element, and take element params:
                    $elementInfo = $this->getElementInfo(substr($outer,5));
                    $str .= '<'.configuration::ELEMENTS[$elementInfo['type']].' id="'.$elementInfo['identifier'].'" class="'.$elementInfo['class'].'">';
                    if ($elementInfo['style'] != null) {
                        json_decode($elementInfo['style']);
                    }
                    // and move down:
                    $str .= $this->createDocumentTree($inner, NULL);
                } else {
                    // if div is empty:
                    $elementInfo = $this->getElementInfo(substr($inner,5));
                    $str .= '<'.configuration::ELEMENTS[$elementInfo['type']].' id="'.$elementInfo['identifier'].'" class="'.$elementInfo['class'].'">';
                    
                    // TODO:
                    if ($elementInfo['style'] != null) {
                        $param = json_decode($elementInfo['style'],true);
                        if (isset($param['css'])) {
                            $styles .= '<style> #'.$elementInfo['identifier'].' { ';
                            foreach($param['css'] as $styleKey => $styleValue) {
                                $styles .= $styleKey.': ' .$styleValue.'; ';
                            }
                            $styles .= '} </style>';
                        }
                        if (isset($param['other'])) {
                            if (isset($param['other']['fish'])) {
                                $str .= $this->fish($param['other']['fish']);
                            }
                        }
                        unset($param);
                    }
                    
                }
                $str .= '</'.configuration::ELEMENTS[$elementInfo['type']].'>'; 
            }
            echo $styles;
            return $str;
            
        }
       
        public function getElementInfo($elementId) 
        {
            return $this->model->getElementInfo($elementId);
        }
       
        public function getDocumentTree($projectId) 
        {
            return $this->model->getDocumentTree($projectId);
        }
        
        public function createTreeArray($htmlTree) 
        {   
        
            // make root, where parentId = 0:
            $treeRoot = array();
            // get roots:
            foreach($htmlTree as $element) {
                if ($element['parentId'] == 0) {
                    $blockName = 'block'.$element['ID'];
                    $treeRoot += array($blockName => $element['ID']);
                    unset($blockName);
                }
            }

            // get branches:
            foreach($treeRoot as $root => $rootId) {
                // if root have any branches:
                if ($rootId > 0) {
                    // delete root id:
                    $treeRoot[$root] = array();
                    $treeRoot[$root] = $this->makeTreeBranches($htmlTree,$rootId);
                } else {
                   $treeRoot[$root] = $root;
                }
            }
            
            // clean brancher without leaves: 
            
            return $treeRoot;
            
        }
    
        public function makeTreeBranches($htmlTree,$rootId) 
        {
            
            // walk throught main $htmlTree:
            foreach($htmlTree as $elementId => $element) {
                // and add branches:
                if ($element['parentId'] == $rootId) {
                    $blockName = 'block'.$element['ID'];
                    $branch[$blockName] = $this->makeTreeBranches($htmlTree,$element['ID']);
                    unset($blockName);
                }
            }
            return $branch;
            
        }
        
        public function cleanLeaves($array) 
        {      
            
            $i = 0;
            foreach($array as $branch => $leaves) {
                // if there is some object in branch:
                if (is_array($leaves)){
                    // move up:
                    $array[$branch] = $this->cleanLeaves($leaves);
                } else {
                    // make string from array: 
                    array_splice($array, $i, 0, $branch);
                    unset($array[$branch]);
                }
                $i++;
            }
            
            return $array;   
            
        }
        
        public function modalHtml($modalId,$modalTitle,$modalBody) 
        { 
            return   '
            <!-- Modal -->
                <div class="modal fade" id="'.$modalId.'" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
                <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="exampleModalLongTitle">'.$modalTitle.'</h3>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">'.$modalBody.'</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
                </div>
                </div>
            <!-- /Modal -->
                ';
        }
       
        public function addContentBlock($post) 
        {
            $count = $this->model->addContentBlock($post['rows'],$post['id'],$post['type'][0]);
            $redirect_to = CONFIGURATION::MAIN_URL.'?page=project&id='.$post['id'].'&new_rows='.$post['rows'].'&id_name='.$post['id_name'].'&class_name='.$post['class_name'];
            header ("Location: $redirect_to");
            exit();  
        }
      
        public function addNewElement($post) 
        {
            // add:
            $this->model->addNewElement($post['rows'],$post['id'],$post['branch_id'],$post['class_name'],$post['type'][0]);
            $redirect_to = CONFIGURATION::MAIN_URL.'?page=project&id='.$post['id'].'&new_rows='.$post['rows'].'&id_name='.$post['id_name'].'&class_name='.$post['class_name'];
            header ("Location: $redirect_to");
            exit(); 
        }
        
        public function deleteElement($post)
        {
            $this->model->deleteElement($post['branch_id']);
            $redirect_to = CONFIGURATION::MAIN_URL.'?page=project&id='.$post['id'].'&deleted='.$post['branch_id'];
            header ("Location: $redirect_to");
            exit(); 
        }
        
        public function favElement($post)
        {
            $this->model->favElement($post['branch_id']);
            $redirect_to = CONFIGURATION::MAIN_URL.'?page=project&id='.$post['id'];
            header ("Location: $redirect_to");
            exit(); 
        }
        
        public function edit_body_style($post) 
        {
           // TODO 
        } 
        
        public function delete_body_option($post)
        {
            // TODO 
        }
        
        public function edit_class_style($post)
        {
            // TODO 
        }
        
        public function delete_class_option($post)
        {
            // TODO
        }
        
        public function editElement($post) {
            
            $style = array();
            $other = array();
            $css = array();
            
            foreach ($post as $key => $value) {
                
                if (in_array($key,configuration::STYLE)) {
                    // TODO: add 'px' and '#' to values
                    $css[$key] = $value;
                }
                if (in_array($key,configuration::OTHER)) {
                    $other[$key] = $value;
                }  
                
            }
            
            if (count($css) > 0 and count($other) > 0) {      
                $style['css'] = $css;
                $style['other'] = $other;
                $this->model->updateElementStyle($post['element_id'],json_encode($style),$post['identifier'],$post['class']);
            } else {
                // if all styles deleted:
               $this->model->updateElementStyle($post['element_id'],NULL,$post['identifier'],$post['class']);
            }
            
            $redirect_to = CONFIGURATION::MAIN_URL.'?page=edit_element&id='.$post['element_id'];
            header ("Location: $redirect_to");
            exit();
            
        }
        
        public function getAllClasses($projectId)
        {
            return $this->model->getAllClasses($projectId);
        }     
        public function addOtherOption($post)
        {
            // get style
            $style = $this->model->getElementInfo($post['id'])['style'];
            // make array from json
            $styleArray = json_decode($style, true);
            // add new other option:
            $styleArray['other'][$post['option'][0]] = $post['value'];
            // save to db:
            $this->model->deleteElementStyle($post['id'],json_encode($styleArray));
            // and go back:
            $redirect_to = CONFIGURATION::MAIN_URL.'?page=edit_element&id='.$post['id'];
            header ("Location: $redirect_to");
            exit();
        }
        public function addCssOption($post)
        {
            // get style
            $style = $this->model->getElementInfo($post['id'])['style'];
            // make array from json
            $styleArray = json_decode($style, true);
            // add new css option:
            $styleArray['css'][$post['option'][0]] = $post['value'];
            // save to db:
            $this->model->deleteElementStyle($post['id'],json_encode($styleArray));
            // and go back:
            $redirect_to = CONFIGURATION::MAIN_URL.'?page=edit_element&id='.$post['id'];
            header ("Location: $redirect_to");
            exit();
        }
        
        public function deleteOtherOption($post)
        {
            // get style
            $style = $this->model->getElementInfo($post['id'])['style'];
            // make array from json
            $styleArray = json_decode($style, true);
            // delete other option:
            unset($styleArray['other'][$post['param']]);
            // save to db:
            $this->model->deleteElementStyle($post['id'],json_encode($styleArray));
            // and go back:
            $redirect_to = CONFIGURATION::MAIN_URL.'?page=edit_element&id='.$post['id'];
            header ("Location: $redirect_to");
            exit();
        }
        
        public function deleteCssOption($post) 
        {
            // get style
            $style = $this->model->getElementInfo($post['id'])['style'];
            // make array from json
            $styleArray = json_decode($style, true);
            // delete css option:
            unset($styleArray['css'][$post['param']]);
            // save to db:
            $this->model->deleteElementStyle($post['id'],json_encode($styleArray));
            // and go back:
            $redirect_to = CONFIGURATION::MAIN_URL.'?page=edit_element&id='.$post['id'];
            header ("Location: $redirect_to");
            exit();
        }
        
        public function addLeaves($post) 
        {
            $this->model->addLeaves($post['block_id'],$post['type'][0],$post['rows'],$post['class_name'],$post['project_id']);
            $redirect_to = CONFIGURATION::MAIN_URL.'?page=project&id='.$post['project_id'].'&new_rows='.$post['rows'].'&id_name='.$post['id_name'].'&class_name='.$post['class_name'];
            header ("Location: $redirect_to");
            exit();
        }
      
      
        public function increasePriority($post) 
        {
            // get full tree:
            $tree = $this->getDocumentTree($post['project_id']);
            
            // search parent id for current branch:
            foreach($tree AS $branch) {
                if ($branch['ID'] == $post['block_id']) {
                    $rootForWork = $branch['parentId'];
                }
            }
            
            $branchArray = array();
            // make current branch array:
            foreach($tree AS $branch) {
                if ($branch['parentId'] == $rootForWork) {
                    $branchArray[$branch['ID']] = $branch['priority'];
                }
            }            
          
            // reverse array:
            $newKeys = array_reverse(array_keys($branchArray));
            $newValues= array_reverse(array_values($branchArray));
            $branchArray = array_combine($newKeys,$newValues);
            $changeArray = array();
            
            $walk = 0;
            $nextElement = 0;
            foreach ($branchArray as $blockId => $priority) {    
                if ($blockId == $post['block_id']) {
                    $walk = 1;
                }
                if ($walk == 1) {
                    $changeArray[$blockId] = $priority;
                }
            }
            
            // increase priority:
            $elementCount = 0;
            foreach($changeArray as $bid => $bpriority) {  

                if ($last_block > 0) {
                    if ($elementCount == 1) {
                        $changeArray[$last_block] = $bpriority + 1;
                        $currentPriority = $bpriority + 1;
                    } else {
                        if ($changeArray[$bid] <= $currentPriority) {
                            $changeArray[$bid] = $currentPriority + 1;
                            $currentPriority = $currentPriority + 1;
                        }
                    }
                }
                
                $last_block = $bid; 
                $last_priority = $bpriority;
                $elementCount = $elementCount + 1;
                
            }

            // insert new priority to DB
            foreach($changeArray as $blockId => $newPriority) {  
                $this->model->updateBlockPriority($blockId, $newPriority);
            }
 
            $redirect_to = CONFIGURATION::MAIN_URL.'?page=project&id='.$post['project_id'];
            header ("Location: $redirect_to");
            exit();
        }
        
        public function decreasePriority($post) 
        {
            
            // get full tree:
            $tree = $this->getDocumentTree($post['project_id']);
            
            // search parent id for current branch:
            foreach($tree AS $branch) {
                if ($branch['ID'] == $post['block_id']) {
                    $rootForWork = $branch['parentId'];
                }
            }
            
            $branchArray = array();
            // make current branch array:
            foreach($tree AS $branch) {
                if ($branch['parentId'] == $rootForWork) {
                    $branchArray[$branch['ID']] = $branch['priority'];
                }
            }
            
            // walk:
            $walk = 0;
            $nextElement = 0;
            foreach ($branchArray as $blockId => $priority) {    
                if ($blockId == $post['block_id']) {
                    $walk = 1;
                }
                if ($walk == 1) {
                    $changeArray[$blockId] = $priority;
                }
            }
            
            // decrease priority:
            $elementCount = 0;
            foreach($changeArray as $bid => $bpriority) {  

                if ($last_block > 0) {
                    if ($elementCount == 1) {
                        $changeArray[$last_block] = $bpriority - 1;
                        $currentPriority = $bpriority - 1;
                    } else {
                        if ($changeArray[$bid] >= $currentPriority) {
                            $changeArray[$bid] = $currentPriority - 1;
                            $currentPriority = $currentPriority - 1;
                        }
                    }
                }
                
                $last_block = $bid; 
                $last_priority = $bpriority;
                $elementCount = $elementCount + 1;
                
            }
            
            // insert new priority to DB
            foreach($changeArray as $blockId => $newPriority) {  
                $this->model->updateBlockPriority($blockId, $newPriority);
            }
            
            $redirect_to = CONFIGURATION::MAIN_URL.'?page=project&id='.$post['project_id'];
            header ("Location: $redirect_to");
            exit();
        }
      
    } // class pure end
    
    require 'class_cron.php';
    require 'classes_pm/class_beautifyDom.php';
    
    
