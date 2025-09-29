<?php
/**
 * Path Helper Functions
 * Provides consistent path management throughout the application
 */

/**
 * Get the base path of the application
 */
function getBasePath() {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $pathInfo = pathinfo($scriptName);
    return $pathInfo['dirname'];
}

/**
 * Get the application root path
 */
function getAppRoot() {
    $currentPath = __DIR__;
    return dirname($currentPath);
}

/**
 * Get path to View directory
 */
function getViewPath($file = '') {
    $basePath = getBasePath();
    return $basePath . '/View/' . $file;
}

/**
 * Get path to Controller directory
 */
function getControllerPath($file = '') {
    $basePath = getBasePath();
    return $basePath . '/Controller/' . $file;
}

/**
 * Get path to includes directory
 */
function getIncludesPath($file = '') {
    $basePath = getBasePath();
    return $basePath . '/includes/' . $file;
}

/**
 * Get path to ProjectBudgets directory
 */
function getProjectBudgetsPath($file = '') {
    $basePath = getBasePath();
    return $basePath . '/ProjectBudgets/' . $file;
}

/**
 * Get path to Edit directory
 */
function getEditPath($file = '') {
    $basePath = getBasePath();
    return $basePath . '/Edit/' . $file;
}

/**
 * Get relative path from current location to target
 */
function getRelativePath($targetPath) {
    $currentPath = dirname($_SERVER['SCRIPT_NAME']);
    $targetDir = dirname($targetPath);
    
    if ($currentPath === $targetDir) {
        return basename($targetPath);
    }
    
    $relativePath = '';
    $currentParts = explode('/', trim($currentPath, '/'));
    $targetParts = explode('/', trim($targetDir, '/'));
    
    // Find common prefix
    $commonLength = 0;
    $minLength = min(count($currentParts), count($targetParts));
    
    for ($i = 0; $i < $minLength; $i++) {
        if ($currentParts[$i] === $targetParts[$i]) {
            $commonLength++;
        } else {
            break;
        }
    }
    
    // Go up from current directory to common ancestor
    for ($i = $commonLength; $i < count($currentParts); $i++) {
        $relativePath .= '../';
    }
    
    // Go down from common ancestor to target
    for ($i = $commonLength; $i < count($targetParts); $i++) {
        $relativePath .= $targetParts[$i] . '/';
    }
    
    $relativePath .= basename($targetPath);
    return $relativePath;
}

/**
 * Get URL-safe path
 */
function getUrlPath($path) {
    return str_replace('\\', '/', $path);
}

/**
 * Check if path is absolute
 */
function isAbsolutePath($path) {
    return strpos($path, '/') === 0 || strpos($path, 'http') === 0;
}

/**
 * Convert absolute path to relative path
 */
function makeRelativePath($absolutePath) {
    if (isAbsolutePath($absolutePath)) {
        $basePath = getBasePath();
        $relativePath = str_replace($basePath, '', $absolutePath);
        return ltrim($relativePath, '/');
    }
    return $absolutePath;
}

/**
 * Get asset path (CSS, JS, images)
 */
function getAssetPath($file) {
    $basePath = getBasePath();
    return $basePath . '/assets/' . $file;
}

/**
 * Get current page name
 */
function getCurrentPage() {
    return basename($_SERVER['SCRIPT_NAME']);
}

/**
 * Get current directory name
 */
function getCurrentDirectory() {
    return basename(dirname($_SERVER['SCRIPT_NAME']));
}

/**
 * Check if current page matches
 */
function isCurrentPage($pageName) {
    return getCurrentPage() === $pageName;
}

/**
 * Check if current directory matches
 */
function isCurrentDirectory($dirName) {
    return getCurrentDirectory() === $dirName;
}

/**
 * Get navigation link with active state
 */
function getNavLink($path, $text, $activeClass = 'active') {
    $currentPage = getCurrentPage();
    $targetPage = basename($path);
    $isActive = ($currentPage === $targetPage) ? $activeClass : '';
    
    return sprintf(
        '<a href="%s" class="%s">%s</a>',
        htmlspecialchars($path),
        htmlspecialchars($isActive),
        htmlspecialchars($text)
    );
}

/**
 * Get form action path
 */
function getFormAction($controller, $action = '') {
    $path = getControllerPath($controller);
    if ($action) {
        $path .= '/' . $action;
    }
    return $path;
}

/**
 * Get include path
 */
function getIncludePath($file) {
    $appRoot = getAppRoot();
    return $appRoot . '/' . $file;
}

/**
 * Debug function to show path information
 */
function debugPath($path) {
    echo "Path: " . $path . "<br>";
    echo "Absolute: " . (isAbsolutePath($path) ? 'Yes' : 'No') . "<br>";
    echo "Relative: " . makeRelativePath($path) . "<br>";
    echo "Base Path: " . getBasePath() . "<br>";
    echo "App Root: " . getAppRoot() . "<br>";
}
?>
