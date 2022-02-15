/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import './styles/navbar.css';

// start the Stimulus application
import './bootstrap';

const $ = require('jquery');

global.$ = global.jQuery = $;

if ($('#upload_type input:checked').val() != 'publication') {
    $('#publication_type').hide();
}
$('#upload_type').change(function() {
    if ($('#upload_type input:checked').val() != 'publication') {
        $('#publication_type').hide();
    } else {
        $('#publication_type').show();
    }
})
$(function () {

    $('#clear-upload').click(function() {
        $("#deposit_form_depositFile").val('');
    })
    function addDeleteBtnAuthor(id,node){
        let deleteBtn = document.createElement("button");
        deleteBtn.innerHTML = $("#creator-fields-list").data('text');
        deleteBtn.className = "btn btn-outline-danger w-100"
        deleteBtn.type = "button";
        deleteBtn.name = "formBtn";
        deleteBtn.id = "author_"+id;
        let deletebtnHtml = document.body.appendChild(deleteBtn);
        $(node).append($(deletebtnHtml).wrap("<div class='col-4 w-100 mb-3 mt-3'></div>").parent());
        $("button[id^=author_]").click(function() {
            const regexIdAuthor = /(\d+)/;
            let idAuthor = this.id.match(regexIdAuthor);
            if (idAuthor[1] === '0' || $("#deposit_form_author_"+idAuthor[1]).length === 0 ) {
                return false;
            }else {
                $("#deposit_form_author_"+idAuthor[1]).parent().remove();
                $(this).parent().remove();
            }
        });
    }
    function addAuthor(){
        let list = $($('.add-another-collection-widget').attr('data-list-selector'));
        let counter = list.data('widget-counter') || list.children().length;
        let editVersion = list.data('edited');

        // prevent the edit version
        if (editVersion === false && counter === 0){
            createNewAuthorFromList(list);
        }

        // add deletebtn to existing list

        if ( editVersion === true && counter > 0){

            // get only deposit_form_author_X
            $("[id^=deposit_form_author_]").filter(function (){
                return /\d+$/.test(this.id)
            }
            ).each(function (index) {
                if (index !== 0){
                    let author_id = index;
                    addDeleteBtnAuthor(author_id,this);
                }
            });

        }

    }
    addAuthor();

    $('.add-another-collection-widget').click(function (e) {

         let list = $($(this).attr('data-list-selector'));
         createNewAuthorFromList(list);
    });


    function createNewAuthorFromList(list){

        // Try to find the counter of the list or use the length of the list
        let counter = list.data('widget-counter') || list.children().length;

        // grab the prototype template
        let newWidget = list.attr('data-prototype');
        // replace the "__name__" used in the id and name of the prototype
        // with a number that's unique to your emails
        // end name attribute looks like name="contact[emails][2]"
        newWidget = newWidget.replace(/__name__/g, counter);
        // Increase the counter
        counter++;
        // And store it, the length cannot be used if deleting widgets is allowed
        list.data('widget-counter', counter);

        // create a new list element and add it to the list
        let newElem = $(list.attr('data-widget-tags')).html(newWidget);

        newElem.appendTo(list);

        let author_id = counter-1;
        if (author_id !==0){
            addDeleteBtnAuthor(author_id,newElem);
        }

    }

    if ($("div#id_deposit").data('id')){
        const idDeposit = $("div#id_deposit").data('id');
        $("button[id^=info_file_]").click(function (e) {
            $('#exampleModal').modal('show');
            if (idDeposit === $("div#id_deposit").data('id')) {
                let tmpFileToDelete = $("div#"+this.id)
                let fileInfo = {
                    "id" : tmpFileToDelete.data('id'),
                    "fileName": tmpFileToDelete.data('filename'),
                    "linkFile": tmpFileToDelete.data('link'),
                    "checksum": tmpFileToDelete.data('checksum')
                };
                let tmpNodeToRemove = tmpFileToDelete.parent();
                let tmpName = tmpFileToDelete.data('filename');
                $("#valid-modal").click(function () {
                    $('#exampleModal').modal('hide');
                    $.ajax({
                        url: '/'+document.documentElement.lang+'/deposit/'+idDeposit+'/delete/file/'+tmpFileToDelete.data('id'),
                        type: 'DELETE',
                        data: JSON.stringify(fileInfo),
                        contentType: "application/json; charset=utf-8",
                        beforeSend: function() {
                            if ($("#ajax_response").length || $("div.alert").length ){
                                $("div.alert").remove();
                            }
                        },
                        success: function(result) {
                            if (result.status === 204){
                                tmpNodeToRemove.remove();
                                $(`<div class="alert alert-success" id="ajax_response">File ${tmpName} Deleted</div>`).insertBefore( "h1" );
                            }else{
                                $("#ajax_response").remove();
                                let link = '';
                                let responseMessageLink = '';
                                if (result.link !== undefined){
                                    link = result.link;
                                    responseMessageLink = `<a href="${link}">${link}</a>`;
                                }
                                $(`<div class="alert alert-danger" id="ajax_response">${result.message} ${responseMessageLink}</div>`).insertBefore( "h1" );
                            }
                        }
                    });
                });
            }
        });
    }
});