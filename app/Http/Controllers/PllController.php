<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Google_Client;
use Google_Service_YouTube_PlaylistSnippet;
use Google_Service_YouTube;
use Config;
use Google_Service_YouTube_PlaylistStatus;
use Google_Service_YouTube_Playlist;
use Google_Service_YouTube_ResourceId;
use Google_Service_YouTube_PlaylistItem;
use Google_Service_Exception;
use Google_Service_YouTube_PlaylistItemSnippet;
use Illuminate\Routing\UrlGenerator;
use App\Pll;

class PllController extends Controller
{
    public $client;

    public function __construct()
    {
        session_start();
        $googleConfig = Config::get('google');
        $this->client = new Google_Client($googleConfig);
    }
    public function index()
    {
        $this->client->setScopes('https://www.googleapis.com/auth/youtube');
        $redirect = url()->to('/') . '/pll';
        $this->client->setRedirectUri($redirect);
        $youtube = new Google_Service_YouTube($this->client);
        if (isset($_GET['code'])) {
            if (strval($_SESSION['state']) !== strval($_GET['state'])) {
                die('The session state did not match.');
            }

            $this->client->authenticate($_GET['code']);
            $_SESSION['token'] = $this->client->getAccessToken();

            return redirect('pll');
        }

        if (isset($_SESSION['token'])) {
            $this->client->setAccessToken($_SESSION['token']);
        }

        $authUrl = '';
        if (empty($this->client->getAccessToken())) {
            $state = mt_rand();
            $this->client->setState($state);
            $_SESSION['state'] = $state;
            $authUrl = $this->client->createAuthUrl();
        }

        return view('pll.index', ['authUrl' => $authUrl]);
    }

    public function search($keyword)
    {
        $youtube = new Google_Service_YouTube($this->client);
        try {
            $searchResponse = $youtube->search->listSearch('id,snippet', [
                'q' => $keyword,
                'maxResults' => 50,
                'type' => 'video'
            ]);
            $videos = [];
            foreach ($searchResponse['items'] as $searchResult) {
                if ($searchResult['id']['kind'] == 'youtube#video') {
                    $videos[] = $searchResult['id']['videoId'];
                }
            }

            return $videos;
        } catch (Google_Service_Exception $e) {
            return $e->getMessage();

        } catch (Google_Exception $e) {
            return $e->getMessage();
        }
    }

    function getTitle($videoId)
    {
        $url = "http://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=". $videoId ."&format=json";
        return json_decode(file_get_contents($url), true)['title'];

    }

    public function createPll(Request $request)
    {
        try {
            if (!isset($_SESSION['token'])) {
                return redirect('pll');
            }
            $htmlBody = [];
            $this->client->setAccessToken($_SESSION['token']);
            $youtube = new Google_Service_YouTube($this->client);
            $dataPll = $request->only(['txtKeyWord', 'txtMyVideo', 'numberPll', 'numberVideo', 'numberMyVideo']);
            $arrKey = explode("\n", str_replace("\r", "", $dataPll['txtKeyWord']));
            $arrKey = array_filter($arrKey);
            $myVideo = explode("\n", str_replace("\r", "", $dataPll['txtMyVideo']));
            $myVideo = array_filter($myVideo);
            $numberPll =  $dataPll['numberPll'];
            $numberVideo =  $dataPll['numberVideo'];
            $numberMyVideo =  $dataPll['numberMyVideo'];
            foreach ($arrKey as $valueKey) {
                $dataTitle = [];
                $xmlTitleYt = file_get_contents('https://clients1.google.com/complete/search?hl=en&gl=en&client=toolbar&ds=yt&q=' . urlencode($valueKey));
                $xmlTitleGg = file_get_contents('https://www.google.com/complete/search?hl=en&gl=en&client=toolbar&ds=&q=' . urlencode($valueKey));

                $arrXml = simplexml_load_string($xmlTitleYt) or $arrXml =  simplexml_load_string($xmlTitleGg);
                $dataXml = json_decode(json_encode($arrXml), true);
                //dd($dataXml['CompleteSuggestion']);exit;
                foreach ($dataXml['CompleteSuggestion'] as $valueXml) {
                    $dataTitle[] = $valueXml['suggestion']['@attributes']['data'];
                }
                $des = '';
                foreach ($dataTitle as $vl) {
                    $des .= $vl . "\n";
                }

                for ($i = 0; $i < $numberPll; $i++) {
                    $videoSearch = $this->search($valueKey);
                    $rdVideo = collect($videoSearch);
                    $arrVideo = $rdVideo->shuffle()->all();

                    $rdMyVideo  =  collect($myVideo);
                    $arrMyVideo = $rdMyVideo->shuffle()->all();

                    $playlistSnippet = new Google_Service_YouTube_PlaylistSnippet();
                    $playlistSnippet->setTitle($dataTitle[$i]);
                    $playlistSnippet->setDescription($des);

                    $playlistStatus = new Google_Service_YouTube_PlaylistStatus();
                    $playlistStatus->setPrivacyStatus('public');

                    $youTubePlaylist = new Google_Service_YouTube_Playlist();
                    $youTubePlaylist->setSnippet($playlistSnippet);
                    $youTubePlaylist->setStatus($playlistStatus);

                    $playlistResponse = $youtube->playlists->insert('snippet,status',
                        $youTubePlaylist, []);
                    $playlistId = $playlistResponse['id'];
                    $htmlBody[] = $playlistId;
                    $resourceId = new Google_Service_YouTube_ResourceId();
                    $resourceId->setVideoId($arrVideo[0]);
                    $resourceId->setKind('youtube#video');
                    $playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
                    $playlistItemSnippet->setPlaylistId($playlistId);
                    $playlistItemSnippet->setResourceId($resourceId);
                    $playlistItem = new Google_Service_YouTube_PlaylistItem();
                    $playlistItem->setSnippet($playlistItemSnippet);
                    $playlistItemResponse = $youtube->playlistItems->insert(
                    'snippet,contentDetails', $playlistItem, []);

                    array_shift($arrVideo);

                    foreach ($arrVideo as $key => $video) {
                        $resourceId = new Google_Service_YouTube_ResourceId();
                        $resourceId->setVideoId($video);
                        $resourceId->setKind('youtube#video');
                        $playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
                        $playlistItemSnippet->setPlaylistId($playlistId);
                        $playlistItemSnippet->setResourceId($resourceId);
                        $playlistItem = new Google_Service_YouTube_PlaylistItem();
                        $playlistItem->setSnippet($playlistItemSnippet);
                        $playlistItemResponse = $youtube->playlistItems->insert(
                        'snippet,contentDetails', $playlistItem, []);
                        if ($key == $dataPll['numberVideo'] - 2) {
                            break;
                        }
                    }

                    foreach ($arrMyVideo as $mKey => $mVideo) {
                        parse_str( parse_url( $mVideo, PHP_URL_QUERY ), $videoId );
                        $resourceId = new Google_Service_YouTube_ResourceId();
                        $resourceId->setVideoId($videoId['v']);
                        $resourceId->setKind('youtube#video');
                        $playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
                        $playlistItemSnippet->setPlaylistId($playlistId);
                        $playlistItemSnippet->setResourceId($resourceId);
                        $playlistItem = new Google_Service_YouTube_PlaylistItem();
                        $playlistItem->setSnippet($playlistItemSnippet);
                        $playlistItemResponse = $youtube->playlistItems->insert(
                        'snippet,contentDetails', $playlistItem, []);
                        if ($mKey == $dataPll['numberMyVideo'] - 1) {
                            break;
                        }
                    }

                }

            }










            // $txtVideo = explode("\n", str_replace("\r", "", $dataPll['txtVideo']));
            // $txtMyVideo = explode("\n", str_replace("\r", "", $dataPll['txtMyVideo']));
            // $txtTitle = explode("\n", str_replace("\r", "", $dataPll['txtTitle']));
            // $txtVideo = array_filter($txtVideo);
            // $txtMyVideo = array_filter($txtMyVideo);
            // $txtTitle = array_filter($txtTitle);

            // $this->client->setAccessToken($_SESSION['token']);
            // $htmlBody = '';
            // $youtube = new Google_Service_YouTube($this->client);



            // foreach ($txtTitle as $title) {
            //     $playlistSnippet = new Google_Service_YouTube_PlaylistSnippet();
            //     $playlistSnippet->setTitle($title);
            //     $playlistSnippet->setDescription($dataPll['txtDes']);

            //     $playlistStatus = new Google_Service_YouTube_PlaylistStatus();
            //     $playlistStatus->setPrivacyStatus('public');

            //     $youTubePlaylist = new Google_Service_YouTube_Playlist();
            //     $youTubePlaylist->setSnippet($playlistSnippet);
            //     $youTubePlaylist->setStatus($playlistStatus);

            //     $playlistResponse = $youtube->playlists->insert('snippet,status',
            //         $youTubePlaylist, []);
            //     $playlistId = $playlistResponse['id'];

            //     $clVideo = collect($txtVideo);
            //     $clMyVideo = collect($txtMyVideo);
            //     $shuffVideo = $clVideo->shuffle();
            //     $shuffMyVideo = $clMyVideo->shuffle();
            //     $fVideo = $shuffVideo->all();
            //     $fMyVideo = $shuffMyVideo->all();

            //     //
            //     $k = array_rand($fVideo);
            //     $v = $fVideo[$k];
            //     parse_str( parse_url( $v, PHP_URL_QUERY ), $videoId );
            //     $resourceId = new Google_Service_YouTube_ResourceId();
            //     $resourceId->setVideoId($videoId['v']);
            //     $resourceId->setKind('youtube#video');
            //     $playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
            //     $playlistItemSnippet->setPlaylistId($playlistId);
            //     $playlistItemSnippet->setResourceId($resourceId);
            //     $playlistItem = new Google_Service_YouTube_PlaylistItem();
            //     $playlistItem->setSnippet($playlistItemSnippet);
            //     $playlistItemResponse = $youtube->playlistItems->insert(
            //     'snippet,contentDetails', $playlistItem, []);

            //     foreach ($fMyVideo as $key => $fMvd) {
            //         parse_str( parse_url( $fMvd, PHP_URL_QUERY ), $videoId );
            //         $resourceId = new Google_Service_YouTube_ResourceId();
            //         $resourceId->setVideoId($videoId['v']);
            //         $resourceId->setKind('youtube#video');
            //         $playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
            //         $playlistItemSnippet->setPlaylistId($playlistId);
            //         $playlistItemSnippet->setResourceId($resourceId);
            //         $playlistItem = new Google_Service_YouTube_PlaylistItem();
            //         $playlistItem->setSnippet($playlistItemSnippet);
            //         $playlistItemResponse = $youtube->playlistItems->insert(
            //         'snippet,contentDetails', $playlistItem, []);
            //         if ($key == $dataPll['numberMyVideo'] - 1) {
            //             break;
            //         }
            //     }

            //     foreach ($fVideo as $key => $fvd) {
            //         parse_str( parse_url( $fvd, PHP_URL_QUERY ), $videoId );
            //         $resourceId = new Google_Service_YouTube_ResourceId();
            //         $resourceId->setVideoId($videoId['v']);
            //         $resourceId->setKind('youtube#video');
            //         $playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
            //         $playlistItemSnippet->setPlaylistId($playlistId);
            //         $playlistItemSnippet->setResourceId($resourceId);
            //         $playlistItem = new Google_Service_YouTube_PlaylistItem();
            //         $playlistItem->setSnippet($playlistItemSnippet);
            //         $playlistItemResponse = $youtube->playlistItems->insert(
            //         'snippet,contentDetails', $playlistItem, []);
            //         if ($key == $dataPll['numberVideo'] - 2) {
            //             break;
            //         }
            //     }

            // }

            // echo 'ok';
            // // $htmlBody .= "<h3>New Playlist</h3><ul>";
            // // $htmlBody .= sprintf('<li>%s (%s)</li>',
            // //     $playlistResponse['snippet']['title'],
            // //     $playlistResponse['id']);
            // // $htmlBody .= '</ul>';

        } catch (Google_Service_Exception $e) {
            $htmlBody = sprintf('<p>A service error occurred: <code>%s</code></p>',
                htmlspecialchars($e->getMessage()));
        } catch (Google_Exception $e) {
            $htmlBody = sprintf('<p>An client error occurred: <code>%s</code></p>',
                htmlspecialchars($e->getMessage()));
        }

        $_SESSION['token'] = $this->client->getAccessToken();
        foreach ($htmlBody as $plval) {
            $pll = new Pll;
            $pll->value = $plval;
            $pll->token = $_SESSION['token']['access_token'];
            $pll->save();
        }

        return redirect('pll')->with('html', $htmlBody);
    }

    public function view()
    {
        $pll = Pll::all();
        foreach ($pll as $pl) {
            echo '<a target="_blank" href="https://www.youtube.com/playlist?list=' . $pl->value . '">'. $pl->value .'</a>'. '<br>';
        }
    }
}
