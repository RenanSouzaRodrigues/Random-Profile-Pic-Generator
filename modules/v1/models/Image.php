<?php 
/**
 * Default framework model
 */
class Image extends FrameRecord 
{
    private $imageDatabase = "image_database.json";

    public function getDatabase() {
        return json_decode(file_get_contents($this->imageDatabase));
    }

    public function getImageFromRecords($index) {
        $database = $this->getDatabase();
        return $database->images[$index];
    }

    public function getAmountOfImagens() {
        $database = $this->getDatabase();
        return count($database->images) - 1;
    }

    /**
     * This method must be set on every model to use it as a static class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}

?>