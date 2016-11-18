window.$ = window.jQuery = require('jquery')
require('bootstrap-sass');

$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': $('meta[name="_token"]').attr('content')
        }
    });

    $('#btnSearch').on('click', function() {
        $.ajax({
            type: 'POST',
            url: 'pll/search',
            data: { keyword: $('#keyword').val(), maxResult: $('#maxResult').val() } ,
            success: function(data) {
                $('#txtVideo').empty().val(data);
            },
            error: function(error) {
                console.log(error);
                alert('An error occurred');
            }
        });
    });

    $('#btnGetMyVideo').on('click', function() {
        $('#txtMyVideo').val('');
        showVideoList($('#channelId').val(), "txtMyVideo", $('#maxVideo').val(), "AIzaSyCdlpfpnVorNF4zHx47wVsnWRd3wnXsuiU");
    })

});

function getJSONData(yourUrl) {
        var Httpreq = new XMLHttpRequest();
        try {
            Httpreq.open("GET", yourUrl, false);
            Httpreq.send(null);
        } catch (ex) {
            alert(ex.message);
        }

        return Httpreq.responseText;
    }

function showVideoList(channelId, textAreaDiv, maxVideo, apiKey) {
    try {
        var videoinfo = JSON.parse(getJSONData("https://www.googleapis.com/youtube/v3/search?order=date&part=snippet&channelId=" + channelId + "&maxResults=" + maxVideo + "&key=" + apiKey));
        var videos = videoinfo.items;
        var videocount = videoinfo.pageInfo.totalResults;
        var videoNum = 0;
        // video listing
        for (var i = 0; i < videos.length; i++) {
            var videoid = videos[i].id.videoId;
            var videotitle = videos[i].snippet.title;
            var videodescription = videos[i].snippet.description;
            var videodate = videos[i].snippet.publishedAt;
            var videothumbnail = videos[i].snippet.thumbnails.default.url;
            if (videoid) {
                document.getElementById(textAreaDiv).value += "https://www.youtube.com/watch?v=" + videoid;
                document.getElementById(textAreaDiv).value += "\n";
                videoNum++;
            }

        }

        document.getElementById("numberMyVideo").innerHTML = '(' + videoNum + ' videos) ';
    } catch (ex) {
        alert(ex.message);
    }
}

