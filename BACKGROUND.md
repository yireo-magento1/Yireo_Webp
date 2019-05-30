# Background
This extension integrates WebP into Magento, and adds WebP images to the page if the browser supports it. Browser support for WebP is detected based on a simple user-agent check (Chrome browser) and an additional JavaScript detection. If WebP is detected, the extension will parse the HTML-output of the Magento root-block to make sure links to supported images (png, jpg, jpeg) are actually replaced with WebP images. 

## Getting the WebP support in your server
For using this extension, you will need to install WebP support on your server. Either upgrade PHP to version 5.5 or higher and make sure the ImageMagick module has webp support built into it. Or download the cwebp binary from the Google WebP project and install that binary on your server:

https://developers.google.com/speed/webp/download

## Configuring the binary in the System Configuration
If your PHP environment does not list WebP as being supported (a simple phpinfo() will tell you whether this is the case or not), or if you don't whether it is being supported, the binary solution is the right way to go for. Once the binary is installed in your environment and you have verified its workings via SSH, you can configure the path to the binary in the Magento System Configuration under Web > Webp images. The binary path that you will configure usually is something like this:

    /usr/local/bin/cwebp

## Configuration options
All configuration options for this module can be found in the System Configuration under Web and then Webp images. The options allow you to enable the 2 WebP conversion methods. If you suspect that one method fails, disable it and see if the other one works.

