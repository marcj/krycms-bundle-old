<?php

namespace Kryn\CmsBundle\Controller\Admin;

use Kryn\CmsBundle\Controller;
use Kryn\CmsBundle\Exceptions\AccessDeniedException;
use Kryn\CmsBundle\Exceptions\FileUploadException;
use Kryn\CmsBundle\File\FileSize;
use Kryn\CmsBundle\Model\Base\FileQuery;

class File extends Controller
{
    /**
     * Removes a file or folder (recursively).
     *
     * @param string $path
     *
     * @return bool
     */
    public function deleteFile($path)
    {
        $this->checkAccess($path);

        FileQuery::create()->filterByPath($path)->delete();
        return $this->getKrynCore()->getWebFileSystem()->remove($path);
    }

    /**
     * Creates a file.
     *
     * @param string $path
     * @param string $content
     *
     * @return bool
     */
    public function createFile($path, $content = '')
    {
        $this->checkAccess($path);
        return $this->getKrynCore()->getWebFileSystem()->put($path, $content);
    }

    public function moveFile($path, $target, $overwrite = false)
    {
        if (!$overwrite && $this->getKrynCore()->getWebFileSystem()->has($target)){
            return ['targetExists' => true];
        }

        $this->checkAccess($path);
        $this->checkAccess($target);

        return $this->getKrynCore()->getWebFileSystem()->move($path, $target);
    }

    /**
     * @param string $target
     * @param array  $files
     * @param bool   $overwrite
     * @param bool   $move
     * @return bool
     */
    public function paste($target, $files, $overwrite = false, $move = false)
    {
        $this->checkAccess($target);
        foreach ($files as $file) {
            $this->checkAccess($file);

            $newPath = $target . '/' . basename($file);
            if (!$overwrite && $this->getKrynCore()->getWebFileSystem()->has($newPath)) {
                return ['targetExists' => true];
            }
        }

        return $this->getKrynCore()->getWebFileSystem()->paste($files, $target, $move ? 'move' : 'copy');
    }



    /**
     * Creates a folder
     *
     * @param string $path
     *
     * @return bool
     */
    public function createFolder($path)
    {
        $this->checkAccess(dirname($path));
        return $this->getKrynCore()->getWebFileSystem()->createFolder($path);
    }

    /**
     * Checks the file access.
     *
     * @param $path
     * @param $fields
     * @param $method
     *
     * @throws \FileIOException
     * @throws \AccessDeniedException
     */
    public function checkAccess($path, $fields = null, $method = null)
    {
        $file = null;

        try {
            $file = $this->getKrynCore()->getWebFileSystem()->getFile($path);
        } catch (\FileNotExistException $e) {
            while ('/' !== $path) {
                try {
                    $path = dirname($path);
                    $file = $this->getKrynCore()->getWebFileSystem()->getFile($path);
                } catch (\FileNotExistException $e) {
                }
            }
        }

        $method = $method ? 'check' . ucfirst($method) . 'Exact' : 'checkUpdateExact';

        if ($file && !$this->getKrynCore()->getACL()->$method('Core\\File', array('id' => $file->getId()), $fields)) {
            throw new \AccessDeniedException(sprintf('No access to file `%s`', $path));
        }
    }

    /**
     * Prepares a file upload process.
     *
     * @param string $path
     * @param string $name
     * @param bool   $overwrite
     * @param bool   $autoRename
     *
     * @return array
     */
    public function prepareUpload($path, $name, $overwrite = false, $autoRename = false)
    {
        $oriName = $name;
        $newPath = ($path == '/') ? '/' . $name : $path . '/' . $name;

        $overwrite = filter_var($overwrite, FILTER_VALIDATE_BOOLEAN);
        $autoRename = filter_var($autoRename, FILTER_VALIDATE_BOOLEAN);

        $this->checkAccess($path);

        $res = array();

        if ($name != $oriName) {
            $res['renamed'] = true;
            $res['name'] = $name;
        }

        $exist = $this->getKrynCore()->getWebFileSystem()->has($newPath);
        if ($exist && !$overwrite) {
            if ($autoRename) {
                //find new name
                $lastDot = strrpos($oriName, '.');
                if (false !== $lastDot) {
                    $firstName = substr($oriName, 0, $lastDot);
                    $extension = substr($oriName, $lastDot + 1);
                }

                $i = 0;
                do {
                    $i++;
                    $name = $firstName .'-'. $i . '.' . $extension;
                    $newPath = ($path == '/') ? '/' . $name : $path . '/' . $name;
                    if (!$this->getKrynCore()->getWebFileSystem()->has($newPath)) {
                        break;
                    }
                } while (true);

                $res['renamed'] = true;
                $res['name'] = $name;
            } else {
                $res['exist'] = true;
                return $res;
            }
        }

        $this->getKrynCore()->getWebFileSystem()->put($newPath, "\0\0\0\0\0\0\0\nKrynBlockedFile\n" . Kryn::getAdminClient()->getTokenId());
        $res['ready'] = true;

        return $res;
    }

    /**
     * Receives the file through $_FILES and place it at the target path.
     *
     * @param string $path
     * @param string $name
     * @param bool   $overwrite
     *
     * @return string
     * @throws \FileUploadException
     * @throws \FileIOException
     * @throws \AccessDeniedException
     */
    public function doUpload($path, $name = null, $overwrite = false)
    {
        $name2 = $_FILES['file']['name'];
        if ($name) {
            $name2 = $name;
        }

        if (!$_FILES['file']) {
            throw new \FileUploadException(sprintf('No file uploaded.'));
        }

        if ($_FILES['file']['error']) {

            $error = '';
            switch ($_FILES['file']['error']) {
                case 1:
                    $error = t('The uploaded file exceeds the upload_max_filesize directive in php.ini.');
                    break;
                case 2:
                    $error =
                        t('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
                    break;
                case 3:
                    $error = t('The uploaded file was only partially uploaded.');
                    break;
                case 7:
                    $error = t('Failed to write file to disk.');
                    break;
                case 6:
                    $error = t('Missing a temporary folder.');
                    break;
                case 4:
                    $error = t('No file was uploaded.');
                    break;
                case 8:
                    $error = t('A PHP extension stopped the file upload.');
                    break;
            }

            $error = sprintf(('Failed to upload the file %s to %s. Error: %s'), $name2, $path, $error);
            //klog('file', $error);

            throw new FileUploadException($error);
        }

        $newPath = ($path == '/') ? '/' . $name2 : $path . '/' . $name2;
        if ($this->getKrynCore()->getWebFileSystem()->has($newPath)) {
            if (!$overwrite) {
                if ($this->getKrynCore()->getWebFileSystem()->has($newPath)) {
                    $content = $this->getKrynCore()->getWebFileSystem()->read($newPath);

                    if ($content != "\0\0\0\0\0\0\0\nKrynBlockedFile\n" . $this->getKrynCore()->getAdminClient()->getTokenId()) {
                        //not our file, so cancel
                        throw new FileUploadException(sprintf('The target file is currently being uploaded by someone else.'));
                    }
                } else {
                    throw new FileUploadException(sprintf('The target file has not be initialized.'));
                }
            }
        }

        $file = $this->getKrynCore()->getWebFileSystem()->getFile(dirname($path));
        if ($file && !$this->getKrynCore()->getACL()->checkUpdate('Core\\File', array('id' => $file->getId()))) {
            throw new AccessDeniedException(sprintf('No access to file `%s`', $path));
        }

        $content = file_get_contents($_FILES['file']['tmp_name']);
        $this->getKrynCore()->getWebFileSystem()->put($newPath, $content);
        @unlink($_FILES["file"]["tmp_name"]);

        return $newPath;
    }


    public function getContent($path)
    {
        if (!$file = self::getFile($path)) {
            return null;
        }

        // todo: check for Read permission

        if ($file['type'] == 'dir'){
            return $this->getFiles($path);
        } else {
            return $this->getKrynCore()->getWebFileSystem()->read($path);
        }
    }

    public function getBinary($path)
    {
        $content = $this->getContent($path);
        die($content);
    }

    /**
     * Returns a list of files for a folder.
     *
     * @param string $path
     *
     * @return array|null
     */
    public function getFiles($path)
    {
        if (!$this->getFile($path)) {
            return null;
        }

        //todo, create new option 'show hidden files' in user settings and depend on that

        $files = $this->getKrynCore()->getWebFileSystem()->getFiles($path);
        return $this->prepareFiles($files);
    }

    public function prepareFiles($files, $showHiddenFiles = false)
    {
        $result = [];

        $blacklistedFiles = array('/index.php' => 1, '/install.php' => 1);

        foreach ($files as $key => $file) {
            $file = $file->toArray();
            if (!$this->getKrynCore()->getACL()->checkListExact('core:file', array('id' => $file['id']))) continue;

            if (isset($blacklistedFiles[$file['path']]) | (!$showHiddenFiles && substr($file['name'], 0, 1) == '.')) {
                continue;
            } else {
                $file['writeAccess'] = $this->getKrynCore()->getACL()->checkUpdate('Core\\File', array('id' => $file['id']));
                $this->appendImageInformation($file);
            }
            $result[] = $file;
        }

        return $result;
    }

    public function appendImageInformation(&$file) {
        $imageTypes = array('jpg', 'jpeg', 'png', 'bmp', 'gif');

        if (array_search($file['extension'], $imageTypes) !== false) {
            $content = $this->getKrynCore()->getWebFileSystem()->read($file['path']);

            $size = new FileSize();
            $size->setHandleFromBinary($content);

            $file['imageType'] = $size->getType();
            $size = $size->getSize();
            if ($size) {
                $file['dimensions'] = ['width' => $size[0], 'height' => $size[1]];
            }
        }
    }

    public function search($path, $q, $depth = 1)
    {
        $files = $this->getKrynCore()->getWebFileSystem()->search($path, $q, $depth);
        return static::prepareFiles($files);
    }

    /**
     * @param string $path
     *
     * @return array|bool|int
     */
    public function getFile($path)
    {
        $file = $this->getKrynCore()->getWebFileSystem()->getFile($path);
        if (!$this->getKrynCore()->getACL()->checkListExact('Core\\File', array('id' => $file->getId()))) {
            return null;
        }

        $file = $file->toArray();
        $file['writeAccess'] = $this->getKrynCore()->getACL()->checkUpdate('Core\\File', $file['id']);

        $this->appendImageInformation($file);

        return $file;
    }

    /**
     * Displays a thumbnail/resized version of a image.
     * This exists the process and sends a `content-type: image/png` http header.
     *
     * @param string $path
     * @param int    $width
     * @param int    $height
     */
    public function showPreview($path, $width = 50, $height = 50)
    {
        if (is_numeric($path)) {
            $path = $this->getKrynCore()->getWebFileSystem()->getPath($path);
        }
        $this->checkAccess($path, null, 'view');
        $file = $this->getKrynCore()->getWebFileSystem()->getFile($path);
        if ($file->isDir()) return;

        $ifModifiedSince = $this->getKrynCore()->getRequest()->headers->get('If-Modified-Since');
        if (isset($ifModifiedSince) && (strtotime($ifModifiedSince) == $file->getModifiedTime())) {
            // Client's cache IS current, so we just respond '304 Not Modified'.
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', $file->getModifiedTime()).' GMT', true, 304);
            exit;
        }

        $image = $this->getKrynCore()->getWebFileSystem()->getResizeMax($path, $width, $height);

        $expires = 3600;
        header("Pragma: public");
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $file->getModifiedTime()).' GMT');
        header("Cache-Control: maxage=" . $expires);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
        header('Content-type: image/png');

        imagepng($image->getResult(), null, 8);
        exit;
    }

    public function setContent($path, $content = '', $contentEncoding = 'plain') {
        $this->checkAccess($path);
        if ('base64' === $contentEncoding){
            $content = base64_decode($content);
        }
        return $this->getKrynCore()->getWebFileSystem()->setContent($path, $content);
    }

    public function viewFile($path) {
        if (is_numeric($path)) {
            $path = $this->getKrynCore()->getWebFileSystem()->getPath($path);
        }
        $this->checkAccess($path, null, 'view');

        $file = $this->getKrynCore()->getWebFileSystem()->getFile($path);
        if ($file->isDir()) return;

        $ifModifiedSince = $this->getKrynCore()->getRequest()->headers->get('If-Modified-Since');
        if (isset($ifModifiedSince) && (strtotime($ifModifiedSince) == $file->getModifiedTime())) {
            // Client's cache IS current, so we just respond '304 Not Modified'.
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', $file->getModifiedTime()).' GMT', true, 304);
            exit;
        }

        $content = $this->getKrynCore()->getWebFileSystem()->read($path);
        $mime = $file->getMimeType();

        $expires = 3600;
        header("Pragma: public");
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $file->getModifiedTime()).' GMT');
        header("Cache-Control: maxage=" . $expires);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
        header('Content-Type: ' . $mime);
        header('Content-length: ' . strlen($content));

        echo $content;
        exit;
    }

    /**
     * Displays a image.
     *
     * @param string $path
     */
    public function showImage($path)
    {
        if (is_numeric($path)) {
            $path = $this->getKrynCore()->getWebFileSystem()->getPath($path);
        }

        $this->checkAccess($path, null, 'view');
        $file = $this->getKrynCore()->getWebFileSystem()->getFile($path);
        if ($file->isDir()) return;

        $ifModifiedSince = $this->getKrynCore()->getRequest()->headers->get('If-Modified-Since');
        if (isset($ifModifiedSince) && (strtotime($ifModifiedSince) == $file->getModifiedTime())) {
            // Client's cache IS current, so we just respond '304 Not Modified'.
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', $file->getModifiedTime()).' GMT', true, 304);
            exit;
        }

        $content = $this->getKrynCore()->getWebFileSystem()->read($path);
        $image = \PHPImageWorkshop\ImageWorkshop::initFromString($content);

        $result = $image->getResult();

        $size = new FileSize();
        $size->setHandleFromBinary($content);

        $expires = 3600;
        header("Pragma: public");
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $file->getModifiedTime()).' GMT');
        header("Cache-Control: maxage=" . $expires);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');

        ob_start();

        if ('png' === $size->getType()) {
            header('Content-Type: image/png');
            imagepng($result, null, 3);
        } else {
            header('Content-Type: image/jpeg');
            imagejpeg($result, null, 100);
        }

        header("Content-Length: ". ob_get_length());
        ob_end_flush();

        exit;
    }

}
