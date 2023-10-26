<?php

class NarakeetPresentationApiClient {
  private $api_key;
  private $poll_interval;
  private $api_url;
  private $extension;
  public function __construct($api_key, $poll_interval = 5, $extension = 'pptx', $api_url = "https://api.narakeet.com") {
    $this->api_key = $api_key;
    $this->poll_interval = $poll_interval;
    $this->api_url = $api_url;
    $this->extension = $extension;
  }
  public function request_upload_token() {
    $options = [
      CURLOPT_URL => "{$this->api_url}/presentation/{$this->extension}/upload-request",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => [
        "x-api-key: $this->api_key",
      ]
    ];
    $curl = curl_init();
    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($httpStatusCode >= 400) {
      throw new Exception("HTTP error: $httpStatusCode $response");
    }
    return json_decode($response, true);
  }
  public function upload_file($upload_token, $local_file) {
    $options = [
      CURLOPT_URL => $upload_token["url"],
      CURLOPT_PUT => true,
      CURLOPT_HTTPHEADER => [
        "Content-Type: " . $upload_token["contentType"]
      ],
      CURLOPT_INFILE => fopen($local_file, 'r'),
      CURLOPT_INFILESIZE => filesize($local_file)
    ];
    $curl = curl_init();
    curl_setopt_array($curl, $options);
    curl_exec($curl);
    $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($httpStatusCode >= 400) {
      throw new Exception("HTTP error: $httpStatusCode $response");
    }
  }
  public function request_conversion($upload_token) {
    $options = [
      CURLOPT_URL => "{$this->api_url}/presentation/{$this->extension}/{$upload_token["uploadId"]}/import",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "x-api-key: $this->api_key",
      ]
    ];

    $curl = curl_init();
    curl_setopt_array($curl, $options);
    $taskResponse = curl_exec($curl);
    $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($httpStatusCode >= 400) {
      throw new Exception("HTTP error: $httpStatusCode $response");
    } 
    $taskResponseData = json_decode($taskResponse, true);
    return $taskResponseData;
  }

  public function request_build($upload_token, $conversion_task, $build_config) {
    $json_request = json_encode($build_config);
    $utf8_request = utf8_encode($json_request);
    $options = [
      CURLOPT_URL => "{$this->api_url}/presentation/{$this->extension}/{$upload_token["uploadId"]}/{$conversion_task["conversionId"]}/build",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $utf8_request,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "x-api-key: $this->api_key",
      ]
    ];
    $curl = curl_init();
    curl_setopt_array($curl, $options);
    $taskResponse = curl_exec($curl);
    $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($httpStatusCode >= 400) {
      throw new Exception("HTTP error: $httpStatusCode $response");
    } 
    $taskResponseData = json_decode($taskResponse, true);
    return $taskResponseData;
  }
  public function poll_until_finished($task, $progress_callback) {
    $options = [
      CURLOPT_URL => $task["statusUrl"],
      CURLOPT_RETURNTRANSFER => true
    ];

    $curl = curl_init();
    curl_setopt_array($curl, $options);

    while (true) {
      $statusResponse = curl_exec($curl);
      $statusResponseData = json_decode($statusResponse, true);
      if ($statusResponseData["finished"]) {
        break;
      } else if ($progress_callback) {
        $progress_callback($statusResponseData);
      }
      sleep($this->poll_interval);
    }
    curl_close($curl);
    return $statusResponseData;
  }
  public function download_to_temp_file($url) {
    $tempFile = tempnam(sys_get_temp_dir(), "video-") . ".mp4";
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FILE, fopen($tempFile, "w"));

    $result = curl_exec($curl);
    $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($httpStatusCode >= 400) {
      throw new Exception("HTTP error: $httpStatusCode $response");
    }
    return $tempFile;
  }
}
?>
