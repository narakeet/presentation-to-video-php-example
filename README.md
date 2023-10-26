# Narakeet Presentation to Video example in PHP

This repository provides a quick example demonstrating how to access the Narakeet presentation to video API from PHP.

The example sends a request to generate a video from a local powerpoint file, then downloads the resulting video into a temporary local file. 

## Prerequisites

This example works with PHP 7.4 and later. You can run it inside Docker (then it does not require a local PHP installation), or on a system with a PHP 7.4 or later.

## Running the example

### run inside docker

1. Copy the powerpoint file you want to convert into the directory with the example (so Docker can see it)
2. Execute `make run NARAKEET_API_KEY=(YOUR API KEY) PPTX_FILE=(PPTX_FILE_TO_CONVERT)`

### run outside docker, on a system with `php` command line, 

1. set the environment variables NARAKEET_API_KEY and PPTX_FILE
2. execute `php main.php`

### example presentation

You can use the `demo.pptx` file in this directory as a quick example. Execute 

```
make run NARAKEET_API_KEY=(YOUR API KEY) PPTX_FILE=demo.pptx
```


