/**
 * Yireo Webp for Magento 
 *
 * @package     Yireo_Webp
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright (c) 2012 Yireo (http://www.yireo.com/)
 * @license     Open Yireo License
 */

(function(){
  var Tester = new Image();
  Tester.onload = function(){
    if(Tester.width == 4 && Tester.height == 4){
        if(webpReplacements) {
            for (var key in webpReplacements) {
                document.body.innerHTML = document.body.innerHTML.replace(key, webpReplacements[key]);
            }
        }
        document.cookie = 'webp=1';
    }
  }
  var WebPTest = 'UklGRkYAAABXRUJQVlA4IDoAAABwAgCdASoEAAQAAYcIhYWIhYSIiQIADAzdrBLeABAAAAEAAAEAAPKn5Nn/0v8//Zxn/6H3QAAAAAA=';
  Tester.src = 'data:image/webp;base64,' + WebPTest;
})();
