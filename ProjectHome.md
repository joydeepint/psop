# Psop - Optimize your web scripts without a cache. #

Currently optimizing following files: **PHP**, **CSS**, **XML**, **JS**, **PHTML**.

(Not finished: .htaccess, asp)

## Why Psop ##
### Optimization ###
No information available about optimization.

### Compression ###
  * Compress files (remove comments and whitespace)
  * Remove hidden directories (like .svn)

### Limitations ###
  * Error messages will not contain the correct line of the error
  * You must have a stable website in order to use psop

### Benchmarks ###
| **Description** | **Time before** | **Time after** | **Relative** |
|:----------------|:----------------|:---------------|:-------------|
| Compiling website with Zend Framework | 33s             | 30s            | **-9%**      |
| Symfony Sandbox Project | 30s             | 28s            | **-7%**      |
| phpBB3 with sample database (16mb) | 31s             | 28s            | **-10%**     |

### Psop benchmarks ###
| **Psop Version** | **Application/Library** | **Time** | **Compression** |
|:-----------------|:------------------------|:---------|:----------------|
| _unreleased_     | Zend Framework          | < 5s     | 38%             |
| _unreleased_     | Symfony Sandbox Project | < 5s     | 43%             |
| _unreleased_     | phpBB3                  | < 5s     | 80%             |