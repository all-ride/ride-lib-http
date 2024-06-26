# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.4.0] - 2024-06-24
### Updated
- Updated to be compatible with php 8.3
##[1.3.1] - 2023-06-05
### Updated
- str_replace won't allow null values fix
## [1.3] - 2023-06-01
### Updated
- Added ReturnTypeWillChange attribute for php 8.1
## [1.2.3] - 2017-06-01
### Updated
- implemented $_SERVER['REQUEST_SCHEME'] to detect HTTPS

## [1.2.2] - 2017-05-30
### Updated
- implemented X-Forwarded-For and Forwarded headers

## [1.2.1] - 2017-03-28
### Updated
- fixed getQueryParametersAsString to return the correct value when queryParameters are not parsed yet
- implemented X-Scheme header to see if incoming request is HTTPS

## [1.2.0] - 2017-01-05
### Updated
- fixed incoming cookie handling when cookie has no value
- minor version to fix earlier version mixup

## [1.0.1] - 2016-09-08
### Updated
- readme
- moved tests autoloader to autoload-dev

## [1.1.0] - 2016-08-10
### Added
- changelog
- setServerUrl to set a default URL for CLI environments

## [1.0.0] - 2016-06-22
### Updated
- updated composer.json for 1.0.0
