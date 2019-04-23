function selectChange() {
  var select = $("#selectSizeId").val();
  var buy = document.getElementById("selectBuy");
  buy.setAttribute('data-id', select);
}

function show(id){
      display = document.getElementById(id).style.display;
      if(display=='none'){
        document.getElementById(id).style.display='block';
      }else{
        document.getElementById(id).style.display='none';
      }
    }
  