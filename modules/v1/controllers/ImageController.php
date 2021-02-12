<?php 

/**
 * Default framework controller
 */
Powerframe::loadModels(['Image'], 'v1');

class ImageController 
{
    public function getRandomImage() {
        try {
            $randInt = rand(0, Image::model()->getAmountOfImagens());
            $selectedImage = Image::model()->getImageFromRecords($randInt);
            echo($selectedImage);
            exit;
        } catch (Exception $e) {
            Powerframe::sendRestResponse(["error" => $e->getMessage()], 500);
        }
        
    }
}

?>