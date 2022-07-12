const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
});

Date.prototype.addSegundos = function(segundos) {
    this.setSeconds(this.getSeconds() + segundos)
};

function fakeNowDate(interval = timeInterval) {
    return Array(interval).fill(0).map((value, index) => {
        let date = new Date();
        date.addSegundos(-index);
        return date.toISOString().replace(/\.\d+/, "");
    });
}
