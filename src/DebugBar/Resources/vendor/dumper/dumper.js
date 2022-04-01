function toggle(e) {
    return e.classList.toggle('dump-show');
}
var dumps = document.getElementsByClassName('dump');
var active = 0;
function collapseAll(e) {
    if (active === 0) {
        active = 1;
        Array.prototype.forEach.call(dumps, function(el) {
            el.classList.add('dump-show');
        });
    } else {
        active = 0;
        Array.prototype.forEach.call(dumps, function(el) {
            el.classList.remove('dump-show');
        });
    }
}