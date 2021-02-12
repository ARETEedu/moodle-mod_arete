<?php

defined('MOODLE_INTERNAL') || die;


 class pagination{
     
      /**
     * creates a pagination for the ARLEMs list.
     *
     * @param $splitet_list a list which contains arrays with arlems in each page
     * @param $page_number id of the active page e.g. 1,2,3
     * @param $id id of the current activity
     * @return string HTML pagination 
     */
    public function getPagination($splitet_list, $page_number, $id){
       $nav = html_writer::start_tag('div', array('class' => 'pagination'));

       $nav .= html_writer::start_tag('a', array('href' => $page_number == 1 ? '#' : 'view.php?id=' . $id . '&pnum=' . strval($page_number-1) )); //back button
       $nav .= 'Prev';
       $nav .= html_writer::end_tag('a');

       for($i = 1; $i < count($splitet_list)+1; $i++)
       {
           //make diffrent color for active page
           if($i == $page_number){
               $pageAttr = array('class' => 'btn btn-primary', 'href' => 'view.php?id='. $id . '&pnum=' . $i );
           }else{
               $pageAttr = array('href' => 'view.php?id='. $id . '&pnum=' . $i );
           }

           $nav .= html_writer::start_tag('a', $pageAttr);
           $nav .= $i;
           $nav .= html_writer::end_tag('a');
       }

       $nav .= html_writer::start_tag('a', array('href' => $page_number == count($splitet_list) ? '#' : 'view.php?id=' . $id . '&pnum=' . strval($page_number+1) )); //back button
       $nav .= 'Next';
       $nav .= html_writer::end_tag('a');

       $nav .= html_writer::end_tag('div');
       
       return $nav;
    }

}