var DudoroffColorizer = BX.namespace('DudoroffColorizer');
var Deals = [];

DudoroffColorizer.isKanban = window.location.pathname.search(/kanban/) >= 0;
DudoroffColorizer.init = async function (type = 2) {
    const dealIds = DudoroffColorizer.dealsOnPage();
    if(Deals.length == dealIds.length && Deals.every((v,i)=>v === dealIds[i])) return;
    Deals = dealIds;

    BX.ajax.runAction('dudoroff:colorizer.api.colorizer.apply',{
        data: {
            ids: dealIds,
            type
        }
    })
    .then(function(res) {
      if(dealIds.length > 0){
        let idsToColor = res.data.response.ids;
        if(idsToColor)
            idsToColor.forEach(dealId => {
                if(DudoroffColorizer.isKanban)
                    document.querySelector(`.main-kanban-grid .main-kanban-item[data-id="${dealId.ID}"] .crm-kanban-item`).style.backgroundColor = dealId.COLOR;
                else
                    document.querySelectorAll(`.main-grid-table .main-grid-row-body[data-id="${dealId.ID}"] .main-grid-cell`).forEach(td => {
                        td.style.backgroundColor = dealId.COLOR;
                    })
            });
    }
    });
};


DudoroffColorizer.coloraizeIds = async function (ids) {
    return new Promise((resolve, reject) => { BX.ajax.get(
        '/bitrix/modules/dudoroff.colorizer/lib/ajax/ajax.php',
        {sessid: BX.bitrix_sessid(), deals: ids,
            action: 'GET_COLOR_IDS'
        },
        function (responce) {
            let ans = JSON.parse(responce);
            if (ans.status && ans.status === 'success'){
                resolve(ans.ids);
            } else {
                if (ans.errors.length > 0){
                    reject(ans.errors)
                    //alert(`Ошибка: ${ans.errors}`);
                }
            }
        });
    })
}

DudoroffColorizer.dealsOnPage = function () {
    const selector = DudoroffColorizer.isKanban ? '.main-kanban-grid .main-kanban-item' : '.main-grid-table .main-grid-row-body:not(.main-grid-not-count)';
    const dealCards = document.querySelectorAll(selector),
        ids = [];
    dealCards.forEach(deal => {
        ids.push(deal.dataset.id);
    });

    return ids;
}

DudoroffColorizer.initAdmin = function(){
    const main_fields_smart = document.querySelectorAll('[name^="main_field"]');
    main_fields_smart.forEach(mField=>{
        mField.addEventListener('change', (e) => {
            DudoroffColorizer.getVals(e.target.querySelector('option:checked').value, mField.name, mField.closest('.adm-detail-content').id)
        })
    })
}

DudoroffColorizer.getVals = function (fieldId, name, type = '') {
    BX.ajax.runAction('dudoroff:colorizer.api.colorizer.fieldvalue',{
        data: {
            fieldId,
            name
        }
    })
    .then(function(res) {
        if(!res.data.error){
            let contId = '';
            if(type){
                contId = type;
                nameParam = 'colors_'+type.replace('edit_', '');
            }
            
            const tab = document.getElementById(contId);
            let startel = tab.querySelector('.color-start');
            let container = startel.closest('tbody');
            let colorEls = container.querySelectorAll('.color');
            if(colorEls){
                colorEls.forEach(removedEl => {
                    removedEl.parentNode.removeChild(removedEl);

                })
            }
            let els = res.data;
            els.forEach(el => {
                let hex = el.HEX ? el.HEX : '#ffffff';
                let tr = document.createElement('tr');
                tr.classList.add('color');
                tr.innerHTML = `<td class="adm-detail-content-cell-l">${el.VALUE}: </td>
                <td class="adm-detail-content-cell-r"><input type="color" name="${nameParam}[${el.ID}]" value="${hex}"></td>`;
                container.appendChild(tr);
            })
            
        }
    });
};