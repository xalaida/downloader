# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Clobbering behaviour (fail, skip, update, replace)
- Possibility to create destination directory on the fly
- Possibility to specify directory using linux path syntax
- Possibility to specify base directory
- Progress hook

### Changed
- Small refactoring
- Return path of downloaded file
- Move exceptions to separate directory
- Make destination nullable

## [0.1.0] - 2022-07-10
### Added
- `Downloader` interface
- `CurlDownloader` class
- `TempFile` class
- `DownloaderException` class
