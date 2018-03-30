<?php
namespace Grav\Plugin;

use Grav\Common\Filesystem\Folder;
use Grav\Common\Grav;
use Grav\Common\Inflector;
use Grav\Common\Plugin;
use Grav\Common\Utils;
use Grav\Common\Backup\ZipBackup;
use RocketTheme\Toolbox\File\JsonFile;

/**
 * Class SquidBackupPlugin
 * @package Grav\Plugin
 */
class SquidBackupPlugin extends Plugin
{

    private $backup_dir;
    private $max;
    private $timer;
    private $auto;
    private $allow_auto;
    private $notifications;

    private $backups;
    private $newest;

    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onAdminTaskExecute' => ['onAdminTaskExecute', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        if (!$this->isAdmin()) {
            return;
        }

        $user = $this->grav['user'];
        if($user->authenticated) {
            if (!$user->authorize('admin.maintenance') || !$user->authorize('admin.super')) {
                return;
            }
            $this->init();
        }
    }

    public function init()
    {
        // get backup folder
        $this->backup_dir = $this->grav['locator']->findResource('backup://', true);
        $this->backups = Folder::all($this->backup_dir);

        // get plugin config
        $this->max = $this->config->get('plugins.squid-backup.max_backups');
        $this->timer = $this->config->get('plugins.squid-backup.notification_timer');
        $this->auto = $this->config->get('plugins.squid-backup.auto_backup');
        $this->allow_auto = $this->config->get('plugins.squid-backup.allow_auto_backups');
        $this->notifications = $this->config->get('plugins.squid-backup.notifications');

        // check if any backups exist
        if (empty($this->backups)) {
            $this->taskBackup();
            return;
        }

        $this->deleteOldest();

        // calculate time difference between newest backup and today
        $time_diff = $this->diff($this->getNewestBackup());

        if ($time_diff >= $this->auto) {
            if($this->allow_auto) {
                $this->taskBackup();
                return;
            }
        }

        if ($time_diff >= $this->timer) {
            if($this->notifications) {
                $this->sendNotification($time_diff);
                return;
            }
        }
    }

    private function getNewestBackup()
    {
        $name = substr(strip_tags($this->grav['config']->get('site.title', basename(GRAV_ROOT))), 0, 20);
        $inflector = new Inflector();

        $backup = explode('.zip', end($this->backups));
        $newest = explode('-', $backup[0]);
        return $this->newest = end($newest);
    }

    private function diff($date1, $length = 'days', $date2 = null) {

        if (is_null($date2)) {
            $date2 = date("Y-m-d");
        }

        $d1 = new \DateTime($date1);
        $d2 = new \DateTime($date2);

        return $d1->diff($d2)->{$length};
    }

    public function sendNotification($diff)
    {

        $param_sep = $this->grav['config']->get('system.param_sep', ':');
        $url = '/' . trim($this->grav['admin']->base,'/') . '/backup.json/task' . $param_sep . 'backup' . '/admin-nonce' . $param_sep . Utils::getNonce('admin-form');

        $this->grav['messages']->add(sprintf($this->grav['language']->translate('PLUGIN_SQUIDBACKUP.TIMER_ALERT'), $diff, $url), 'info');
    }

    protected function deleteOldest()
    {
        if (count($this->backups) > $this->max)
        {
            unlink($this->backup_dir.'/'.$this->backups[0]);
        }
    }

    protected function taskBackup()
    {
        $param_sep = $this->grav['config']->get('system.param_sep', ':');

        $download = $this->grav['uri']->param('download');

        if ($download) {
            $file             = base64_decode(urldecode($download));
            $backups_root_dir = $this->backup_dir;

            if (substr($file, 0, strlen($backups_root_dir)) !== $backups_root_dir) {
                header('HTTP/1.1 401 Unauthorized');
                exit();
            }

            Utils::download($file, true);
        }

        $log = JsonFile::instance($this->grav['locator']->findResource("log://backup.log", true, true));

        try {
            $backup = ZipBackup::backup();
        } catch (\Exception $e) {

            $messages = $this->grav['messages'];
            $admin = $this->grav['admin'];
            $messages->add($admin->translate('PLUGIN_SQUIDBACKUP.AUTO_BACKUP_ERROR'), 'info');
            return true;
        }

        $download = urlencode(base64_encode($backup));
        $url      = rtrim($this->grav['uri']->rootUrl(true), '/') . '/' . trim($this->grav['admin']->base,
                '/') . '/task' . $param_sep . 'backup/download' . $param_sep . $download . '/admin-nonce' . $param_sep . Utils::getNonce('admin-form');

        $log->content([
            'time'     => time(),
            'location' => $backup
        ]);
        $log->save();

        $messages = $this->grav['messages'];
        $admin = $this->grav['admin'];
        $messages->add(sprintf($admin->translate('PLUGIN_SQUIDBACKUP.AUTO_BACKUP'), $url), 'info');

        return true;
    }

}
