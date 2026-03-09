/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!*********************************!*\
  !*** ./resources/js/backend.js ***!
  \*********************************/
$(document).ready(function () {
  toastr.options = {
    "positionClass": "toast-bottom-right",
    "progressBar": true
  };
  window.addEventListener('hide-form', function (event) {
    $('#form').modal('hide');
    toastr.success(event.detail.message, 'Success!');
  });
});
window.addEventListener('show-form', function (event) {
  $('#form').modal('show');
});
window.addEventListener('show-delete-modal', function (event) {
  $('#confirmationModal').modal('show');
});
window.addEventListener('hide-delete-modal', function (event) {
  $('#confirmationModal').modal('hide');
  toastr.success(event.detail.message, 'Success!');
});
window.addEventListener('alert', event => { 
             toastr[event.detail.type](event.detail.message, 
             event.detail.title ?? ''), toastr.options = {
                    "closeButton": true,
                    "progressBar": true,
                }
            });
window.addEventListener('updated', function (event) {
  toastr.success(event.detail.message, 'Success!');
});
$('[x-ref="profileLink"]').on('click', function () {
  localStorage.setItem('_x_currentTab', '"profile"');
});
$('[x-ref="changePasswordLink"]').on('click', function () {
  localStorage.setItem('_x_currentTab', '"changePassword"');
});

window.addEventListener('refreshPage', event => {
  let message = event.detail.message
  location.reload()
}) 

window.addEventListener('hide-form-centros', function (event) {
  $('#form-centros').modal('hide');
  toastr.success(event.detail.message, 'Success!');
});

window.addEventListener('show-form-centros', function (event) {
  $('#form-centros').modal('show');
});

/******/ })()
;