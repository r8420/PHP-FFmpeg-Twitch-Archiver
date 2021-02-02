var topLoader = new Array(), startstopTimer, startstopCurrent = 0;
;

// Padding function
function pad(number, length) {
    var str = '' + number;
    while (str.length < length) {
        str = '0' + str;
    }
    return str;
}

function initPoll(fkey) {
    var statusData;

    $.ajax(jsNS.post_url, {
        type: 'POST',
        dataType: 'json',
        async: false,
        data: {'fkey': fkey, 'type': 'status'},
        success: function (data) {
            statusData = data;
            startPolling(data, fkey);
        },
        error: function (data) {
            console.log(data);
            console.log('Polling failed! ' + fkey);
            window.location.reload();
            statusData = false;
        }
    });
    // Initialize the progress loader
    topLoader[fkey] = $("#progress" + fkey).percentageLoader({
        width: 150,
        height: 150,
        controllable: false,
        value: '00:00:00',
        progress: 0,
        onProgressUpdate: function (val) {
            topLoader.setValue(Math.round(val * 100.0));
        }
    });
    return statusData;
};

function pollStatus(fkey) {
    var statusData;

    $.ajax(jsNS.post_url, {
        type: 'POST',
        dataType: 'json',
        async: false,
        data: {'fkey': fkey, 'type': 'status'},
        success: function (data) {
            statusData = data;

        },
        error: function () {
            console.log('Download error!');
            location.reload();
            statusData = false;
        }
    });
    return statusData;
};

function startPolling(data, fkey) {
    console.log('start polling: ' + fkey);
    var currentTime, totalTime, hrCurrentTime, hrTotalTime, statData, intPoll, count;
    count = 0;

    currentTime = data.time_encoded;
    totalTime = data.time_total;

    intPoll = setInterval(function () {
        if (currentTime < totalTime) {
            statData = pollStatus(fkey, currentTime);
            //console.log(statData);
            if (!statData) {
                //alert('Bad data!');
                //console.log(statData);
                clearInterval(intPoll);
                return false;
            }
            currentTime = statData.time_encoded;
            totalTime = statData.time_total;
            hrCurrentTime = statData.time_encoded_min;
            hrTotalTime = statData.time_total_min;

            topLoader[fkey].setProgress(currentTime / totalTime);
            topLoader[fkey].setValue(hrCurrentTime + ' / ' + hrTotalTime);
        } else {
            clearInterval(intPoll);
            console.log('Finished!');
            window.location.reload();
        }
    }, 1000);
}

(function ($) {

    var fkeys = [];

})(jQuery);
