/*
Template Name: Fabkin - Admin & Dashboard Template
Author: Codebucks
File: Academy list init js
*/

document.addEventListener('DOMContentLoaded', function () {
    let status = document.getElementById('status');
    if (status) {
        const choicesstatus = new Choices('#status', {
            placeholderValue: 'Select Status',
            searchPlaceholderValue: 'Search...',
            removeItemButton: true,
            itemSelectText: 'Press to select',
        });
    }
});