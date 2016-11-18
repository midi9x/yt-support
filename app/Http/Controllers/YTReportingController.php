<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Google_Client;

use Google_Service_YouTubeReporting;

use Config;

class YTReportingController extends Controller
{
    public $htmlBody;
    public function index()
    {
        session_start();
        $googleConfig = Config::get('google');
        $client = new Google_Client($googleConfig);
        $client->setScopes('https://www.googleapis.com/auth/yt-analytics-monetary.readonly');
        $redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
            FILTER_SANITIZE_URL);
        // $client->setRedirectUri($redirect);

        $youtubeReporting = new Google_Service_YouTubeReporting($client);

        if (isset($_GET['code'])) {
            if (strval($_SESSION['state']) !== strval($_GET['state'])) {
                die('The session state did not match.');
            }

            $client->authenticate($_GET['code']);
            $_SESSION['token'] = $client->getAccessToken();
            header('Location: ' . $redirect);
        }

        if (isset($_SESSION['token'])) {
            $client->setAccessToken($_SESSION['token']);
        }

        if ($client->getAccessToken()) {
            try {
                if (empty($this->listReportTypes($youtubeReporting, $this->htmlBody))) {
                    $this->htmlBody .= sprintf('<p>No report types found.</p>');
                } else if (isset($_GET['reportTypeId'])){
                    $this->createReportingJob($youtubeReporting, $_GET['reportTypeId'], $_GET['jobName'], $this->htmlBody);
                }
            } catch (Google_Service_Exception $e) {
                $this->htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
                    htmlspecialchars($e->getMessage()));
            } catch (Google_Exception $e) {
                $this->htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
                    htmlspecialchars($e->getMessage()));
            }
            $_SESSION['token'] = $client->getAccessToken();
        } else {
            $state = mt_rand();
            $client->setState($state);
            $_SESSION['state'] = $state;

            $authUrl = $client->createAuthUrl();
            $this->htmlBody = '<h3>Authorization Required</h3>
            <p>You need to <a href="' . $authUrl . '">authorize access</a> before proceeding.<p>';
        }

        return $this->htmlBody;
    }

    function createReportingJob(Google_Service_YouTubeReporting $youtubeReporting, $reportTypeId,
        $name, &$htmlBody) {
        $reportingJob = new Google_Service_YouTubeReporting_Job();
        $reportingJob->setReportTypeId($reportTypeId);
        $reportingJob->setName($name);

        $jobCreateResponse = $youtubeReporting->jobs->create($reportingJob);

        $htmlBody .= "<h2>Created reporting job</h2><ul>";
        $htmlBody .= sprintf('<li>"%s" for reporting type "%s" at "%s"</li>',
            $jobCreateResponse['name'], $jobCreateResponse['reportTypeId'], $jobCreateResponse['createTime']);
        $htmlBody .= '</ul>';
    }

    function listReportTypes(Google_Service_YouTubeReporting $youtubeReporting, &$htmlBody) {
        $reportTypes = $youtubeReporting->reportTypes->listReportTypes();

        $htmlBody .= "<h3>Report Types</h3><ul>";
        foreach ($reportTypes as $reportType) {
            $htmlBody .= sprintf('<li>id: "%s", name: "%s"</li>', $reportType['id'], $reportType['name']);
        }
        $htmlBody .= '</ul>';

        return $reportTypes;
    }
}
