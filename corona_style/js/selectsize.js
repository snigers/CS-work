function selectChange() {
  var select = $("#selectSizeId").val();
  document.getElementById("selectId").innerHTML = select
  var buy = document.getElementById("selectBuy");
  buy.setAttribute('data-id', select); 
}