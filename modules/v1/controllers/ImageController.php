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
            Powerframe::sendRestResponse(['img'=> $selectedImage], 200);
        } catch (Exception $e) {
            Powerframe::sendRestResponse(["error" => $e->getMessage()], 500);
        }
        
    }
}

?>