/**
 *--------------------------
 * Reusable functions
 *--------------------------
 */

function imagePreview(input, selector) {
    if (input.files && input.files[0]) {
        var render = new FileReader();

        render.onload = function (e) {
            $(selector).attr("src", e.target.result);
        };

        render.readAsDataURL(input.files[0]);
    }
}

let searchPage = 1;
let noMoreDataSearch = false;
let searchTempVal = "";
let setSearchLoading = false;

function searchUsers(query) {
    if (query != searchTempVal) {
        let searchPage = 1;
        let noMoreDataSearch = false;
    }
    searchTempVal = query;

    if (!setSearchLoading && !noMoreDataSearch) {
        $.ajax({
            url: "/messenger/search",
            type: "GET",
            data: {
                query: query,
                page: searchPage,
            },
            beforeSend: function (data) {
                setSearchLoading = true;
                let loader = `
                <div class="text-center search-loader">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
               `;

                $(".user_search_list_result").append(loader);
            },
            success: function (data) {
                setSearchLoading = false;
                $(".user_search_list_result").find(".search-loader").remove();
                if (searchPage < 2) {
                    $(".user_search_list_result").html(data.records);
                } else {
                    $(".user_search_list_result").append(data.records);
                }

                noMoreDataSearch = searchPage >= data?.last_page;
                if (!noMoreDataSearch) searchPage += 1;
            },
            error: function (xhr, status, error) {
                setSearchLoading = false;
                $(".user_search_list_result").find(".search-loader").remove();
            },
        });
    }
}

function debounce(callback, delay) {
    let timerId;
    return function (...args) {
        clearTimeout(timerId);
        timerId = setTimeout(() => {
            callback.apply(this, args);
        }, delay);
    };
}

function actionOnScroll(selector, callback, topScroll = false) {
    $(selector).on("scroll", function () {
        let element = $(this).get(0);
        const condition = topScroll
            ? element.scrollTop == 0
            : element.scrollTop + element.clientHeight >= element.scrollHeight;

        if (condition) {
            callback();
        }
    });
}

/**
 *--------------------------
 * On Dom Load
 *--------------------------
 **/

$(document).ready(function () {
    $("#select_file").change(function () {
        imagePreview(this, ".profile-image-preview");
    });

    // todo: Search Users
    const debounceSearch = debounce(function () {
        const value = $(".user_search").val();
        searchUsers(value);
    }, 500);

    $(".user_search").on("keyup", function () {
        let query = $(this).val();
        if (query.length > 0) {
            debounceSearch();
        }
        // searchUsers(query);
    });

    // todo: Search Pagination
    actionOnScroll(".user_search_list_result", function () {
        let value = $(".user_search").val();
        searchUsers(value);
    });
});
