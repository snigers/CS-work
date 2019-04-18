<?php
class exchange_custom extends def_module {



    public function getGroupId($id = false){
        $id = $id ? $id : getRequest('id');
        if($id){
            $id = explode("#",$id);
            $id = $id[0];
            $sourceId = 5;
            $umiImportRelations = umiImportRelations::getInstance();
            $id = $umiImportRelations->getNewIdRelation($sourceId,$id);
            if($id){
                $umiHierarchy = umiHierarchy::getInstance();
                $page = $umiHierarchy->getElement($id);
                if($page){
                    $parentId = $page->getParentId();
                    $parentId_1c = $umiImportRelations->getOldIdRelation($sourceId,$parentId);
                    if($parentId_1c){
                        return def_module::parseTemplate('',
                            array(
                                'status' => 'ok',
                                'id' => $parentId_1c
                            )
                        );
                    }
                }
            }
        }
        return def_module::parseTemplate('',array('status' => 'error'));
    }

    public function fixRelaton(){
        $pages = new selector('pages');
        $pages->types('hierarchy-type')->name('catalog', 'object');
        //$pages->where('is_active')->equals(array(0));
        $pages->where("this_parent")->equals(array(1));
        foreach($pages as $page){
            $sub_pages = new selector('pages');
            $sub_pages->types('hierarchy-type')->name('catalog', 'object');
            $sub_pages->where("this_child")->equals(1);
            $sub_pages->where('hierarchy')->page($page->id)->childs(1);
            if($sub_pages->length){
                $sub_pages = $sub_pages->result;
                $page->childs_product = $sub_pages;
                $sizes = array();
                foreach($sub_pages as $sub_page){
                    $sizeId = $sub_page->razmer;
                    if($sizeId){
                        $sizes[$sizeId] = $sizeId;
                    }
                    $sub_page->parent_product = $page->id;
                }
                if(sizeof($sizes)){
                    $page->sizes = $sizes;
                }
            }
        }
    }
}

?>