// Pause browser and events for certain amount of time start
function pauseBrowser(millis) {
    var date = Date.now();
    var curDate = null;
    do {
        curDate = Date.now();
    } while (curDate-date < millis);
}
/// Call it like below:
pauseBrowser(2000);
// Pause browser and events for certain amount of time finish....
