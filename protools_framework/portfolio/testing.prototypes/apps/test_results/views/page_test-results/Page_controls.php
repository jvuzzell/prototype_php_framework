<?php 

class Page_presentation {
     
    protected $model; 
    
    public function __construct( $page_model ) {
        $this->model = $page_model;
    }
    
    public function returnTitle() {
        return $this->model[ 'title' ];
    } 

    public function returnNewContent() {
        return 'this is new content: ' . $this->newContent;
    }
    
}