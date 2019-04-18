
async function fillAlts(){
  let imgAlts = document.querySelectorAll('img');
  imgAlts.forEach((el)=>{
    if(el.alt == ''){
    el.alt = "женская одежда из кашемира coronastyle";
    console.log('ok');
    }
  });
};
fillAlts();