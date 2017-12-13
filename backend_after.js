document.forms.update_category_form.category_id.addEventListener('change', function() {
    //var text = ''; for (var k in this) {text += k +': '+ this[k]+'\n';}
    //alert(text)
    if (this.value == 0) {var value = ''; var value2="Создать";}
    else {var value = this[this.selectedIndex].textContent; var value2 = 'Переименовать';}
    document.forms.update_category_form.category_name.value = value;
    document.forms.update_category_form.button.value = value2;
});