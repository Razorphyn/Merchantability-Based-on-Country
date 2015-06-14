var razorphynPathUpdateCountry,
	razorphynCurrentProductCountry,
	razorphynCurrentCategoryCountry;
	
document.observe('dom:loaded', function(){
	
	document.getElementById("razorphyn_country").addEventListener('change', function(){//check
		if (!Array.prototype.indexOf) {
			Array.prototype.indexOf = function(obj, start) {
				for (var i = (start || 0), j = this.length; i < j; i++) {
					if (this[i] === obj) { return i; }
				}
				return -1;
			};
		}

		var e = this,
			country = e.options[e.selectedIndex].value;

		new Ajax.Request(razorphynPathUpdateCountry, {
				method: 'POST',
				parameters: {country:country,category_id:razorphynCurrentCategoryCountry,product_id:razorphynCurrentProductCountry},
				onSuccess:function(data){
								if (200 == data.status){
									/* 
										0-> remove? true:false
										1-> product? true:false
										2-> button? true:false
										3-> nodeName
										4-> items to remove
										5-> button classes
										6-> Replace phrase
									*/	
									if(data[0]==true){
										JSON.parse(data[4]);
										var fNode=(data[2])? data[3]:"FORM",
											el=document.getElementsByTagName(fNode),
											c=el.length,
											id,
											replaceP= document.createElement('p'),
											reg= /checkout\/.?cart\/add\/.+\/([0-9]+)\//;
											
										replaceP.innerHTML=data[6];

										if(fNode=="FORM"){
											for(i=0;i<c;i++){
												id=(el[i].getAttribute('action').match(reg));
												id=id[0];
												if(data[4].indexOf(id)!=-1){
													var btChildren = el[1].querySelectorAll(data[3]+data[4]),
														btC= btChildren.length;

													for(j=0;j<btC;i++){
														el[i].replaceChild(replaceP,btChildren[j]);
													}
												}
											}
										}
										else{
											data[5]=trim(data[5].replace('.',' '));
											for(i=0;i<c;i++){
												if(el[i].getAttribute('class')==data[5]){
													id=(el[i].getAttribute('onclick').match(reg));
													id=id[0];
													if(data[4].indexOf(id)!=-1){
														el[i].parentNode.replaceChild(replaceP,el[i]);
													}
												}
											}
										}
									}
									else if(data[0]=='refresh'){
										location.reload();
									}
									else if(data[0]=='e')
										alert(data[1]);
								}
								else{
									alert("Can't contact server" );
								}
					},
				onFailure:  function (data){
								alert("Country update failed" );
							}
			});
	});
});
/*
window.onunload=function(){
	var x= document.getElementById("razorphyn_country");
	x.remove(x.selectedIndex);
};*/