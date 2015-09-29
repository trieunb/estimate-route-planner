<?php
final class Base64Encoder
{
    public static function decode($encodedContent) {
        $dataPieces = explode(",", $encodedContent);
        $encodedImage = $dataPieces[1];
        return base64_decode($encodedImage);
    }
}
?>
