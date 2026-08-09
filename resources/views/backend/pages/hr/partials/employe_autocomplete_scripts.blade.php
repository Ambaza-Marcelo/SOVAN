@once
<script>
window.hrFormatEmployeLabel = function (emp) {
    if (emp.matricule) {
        return emp.matricule + ' — ' + emp.name;
    }
    return emp.name;
};

window.initHrEmployeAutocomplete = function (config) {
    var input = $('#' + config.inputId);
    var hidden = $('#' + config.hiddenId);
    var list = $('#' + config.listId);
    var employees = config.employees || [];
    var allowEmpty = config.allowEmpty !== false;
    var onSelect = typeof config.onSelect === 'function' ? config.onSelect : null;
    var selectedIndex = -1;

    input.off('input.hrEmployeAutocomplete keydown.hrEmployeAutocomplete');

    input.on('input.hrEmployeAutocomplete', function () {
        var searchText = $(this).val().toLowerCase().trim();
        list.empty();
        selectedIndex = -1;

        if (searchText === '') {
            list.hide();
            hidden.val('');
            if (onSelect) {
                onSelect('');
            }
            return;
        }

        var filtered = employees.filter(function (item) {
            return item.name.toLowerCase().includes(searchText)
                || (item.matricule && item.matricule.toLowerCase().includes(searchText))
                || window.hrFormatEmployeLabel(item).toLowerCase().includes(searchText);
        });

        if (filtered.length > 0) {
            filtered.forEach(function (item) {
                var div = $('<div></div>')
                    .text(window.hrFormatEmployeLabel(item))
                    .attr('data-id', item.id);
                div.on('click', function () {
                    input.val($(this).text());
                    hidden.val($(this).attr('data-id'));
                    list.hide();
                    if (onSelect) {
                        onSelect($(this).attr('data-id'));
                    }
                });
                list.append(div);
            });
            list.show();
        } else {
            list.hide();
            if (!allowEmpty) {
                hidden.val('');
            }
        }
    });

    input.on('keydown.hrEmployeAutocomplete', function (e) {
        var items = list.find('div');

        if (e.keyCode === 40) {
            e.preventDefault();
            selectedIndex = (selectedIndex < items.length - 1) ? selectedIndex + 1 : selectedIndex;
            items.removeClass('autocomplete-active');
            if (selectedIndex >= 0) {
                $(items[selectedIndex]).addClass('autocomplete-active');
            }
        } else if (e.keyCode === 38) {
            e.preventDefault();
            selectedIndex = (selectedIndex > 0) ? selectedIndex - 1 : -1;
            items.removeClass('autocomplete-active');
            if (selectedIndex >= 0) {
                $(items[selectedIndex]).addClass('autocomplete-active');
            }
        } else if (e.keyCode === 13) {
            e.preventDefault();
            if (selectedIndex >= 0 && items.length > 0) {
                $(items[selectedIndex]).click();
            }
        } else if (e.keyCode === 27) {
            list.hide();
        }
    });
};

$(document).on('click', function (e) {
    if (!$(e.target).closest('.autocomplete-wrapper').length) {
        $('.autocomplete-list').hide();
    }
});
</script>
<style>
.autocomplete-list div {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #d4d4d4;
}
.autocomplete-list div:hover,
.autocomplete-active {
    background-color: #e9e9e9;
}
.autocomplete-list div:last-child {
    border-bottom: none;
}
</style>
@endonce
