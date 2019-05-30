# Which Webp method do you recommend to use?
The binary method. The PHP GD support for WebP is quite new, depends on your specific PHP version and it sometimes does not work. By using the WebP binaries from Google instead, you will be able to use the latest WebP technology with the latest bugfixes.

# After installing WebP, some of the images are blank
For each image, the WebP extension tries to convert the original image to a WebP alternative. This might fail if there are unsupported features in the image (animated GIFs, certain alpha-transparencies). Our recommendation is to use the latest WebP binaries as conversion method, instead of PHP GD.

# Should I manually upload the WebP variants of all my images?
You can, but that's not a must. This extension will try to convert your current images, by using either the cwebp binary or the PHP GD library. If both fail, or the result is not as you want it to be, you can still try to convert your images to WebP manually (with whatever tool you can find) and upload the WebP images to your application folders. This extension will simply check whether a WebP alternative exists in the same folder as the original image. Note that the extension will also compare time stamps.
