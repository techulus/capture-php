[![N|Solid](https://s3-ap-southeast-1.amazonaws.com/capture-techulus/logo.png)](https://capture.techulus.in/)

# Capture PHP

Get your API Key and Secret from https://capture.techulus.in/console

```php
<?php
$API_URL = 'https://cdn.capture.techulus.in/';

$your_api_key = 'API_KEY_FROM_CONSOLE';
$your_api_secret = 'API_SECRET_FROM_CONSOLE';

// Target URL
$input_url = urlencode('http://techulus.in/');
$hash = md5($your_api_secret . 'url=' . $input_url);

// Image URL
$result_img_url = $API_URL . $your_api_key . '/' . $hash . '/image?url=' . $input_url;

// PDF URL
$result_pdf_url = $API_URL . $your_api_key . '/' . $hash . '/pdf?url=' . $input_url;

echo $result_img_url;
?>
```
