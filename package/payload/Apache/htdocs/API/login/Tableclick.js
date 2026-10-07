document.addEventListener('DOMContentLoaded', () => {
    // Delegated to #flex1 because flexigrid destroys and recreates rows on every reload.
    // Clicking anywhere in a row selects that row's region for the command buttons.
    // Flexigrid stamps each cell with the colModel name as abbr, so this finds the
    // Name cell no matter which position the column renders in.
    $('#flex1').on('click', 'td', function () {
        var regionName = $(this).closest('tr').find("td[abbr='RegionName'] div").text().trim();
        if (regionName) {
            $("#RegionName").val(regionName);
            $("#result").text("Selected region: " + regionName);
        }
    });
});
