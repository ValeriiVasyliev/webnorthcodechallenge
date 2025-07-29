# 🚀 WebNorth Code Challenge Plugin

## 📑 Table of Contents

* [Requirements](#requirements)
* [Installation](#installation)
    * [Install Composer](#3-install-composer)
* [Plugin Structure](#plugin-structure-fever-code-challenge)
* [Developer Tools & Testing](#developer-tools--testing)
    * [Available Composer Scripts](#available-composer-scripts)
    * [How to Use](#how-to-use)

---

## ✅ Requirements

Ensure the following tools are installed on your system before starting the project:

| Requirement       | Check Command        | Installation Link                                                                      |
| ----------------- | -------------------- | -------------------------------------------------------------------------------------- |
| PHP >= 8.3        | `php -v`             | [php.net](https://www.php.net/manual/en/install.php)                                   |
| Composer >= 2.1.6 | `composer --version` | [getcomposer.org](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) |

---

## ⚙️ Installation

### Install Composer

Composer manages PHP dependencies, including WordPress core and plugins.

#### macOS:

```bash
brew install composer
```

#### Ubuntu/Debian:

```bash
sudo apt install composer
```

Or download from the official site:
👉 [https://getcomposer.org/download](https://getcomposer.org/download)

---

---

## 🔌 Plugin Structure: `webnorthcodechallenge`

## 🧪 Developer Tools & Testing

The `webnorthcodechallenge` plugin includes a set of developer tools to ensure code quality, consistency, and test coverage. These are configured in `composer.json` and can be executed using Composer scripts.

### 🧰 Available Composer Scripts

| Script             | Description                                                               |
| ------------------ | ------------------------------------------------------------------------- |
| `phpcs:config-set` | Sets the paths for coding standard rulesets (`WPCS`, `PHPCompatibility`). |
| `phpcs`            | Runs code linting using the rules defined in `phpcs.xml`.                 |
| `phpcbf`           | Automatically fixes style issues based on coding standards.               |
| `test`             | Executes PHPUnit tests with the bootstrap file.                           |
| `pot`              | Generates a `.pot` file for translations using WP-CLI's i18n command.     |

---

### ✅ How to Use

Run the following commands from anywhere in your terminal:

#### 📌 Set PHP CodeSniffer Paths

```bash
composer run phpcs:config-set
```

This configures PHPCS to recognize WordPress coding standards and PHP compatibility rules.

#### 🧹 Run Code Style Check

```bash
composer run phpcs
```

Scans PHP files for style violations using rules in `phpcs.xml`.

#### ✨ Auto-fix Style Issues

```bash
composer run phpcbf
```

Automatically fixes fixable style violations.

#### 🧪 Run Tests

```bash
composer run test
```

#### 🌐 Generate Translation File

```bash
composer run pot
```