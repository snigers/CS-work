function selectChange() {
  var select = $("#selectSizeId").val();
  var buy = document.getElementById("selectBuy");
  buy.setAttribute('data-id', select);
}