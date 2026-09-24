<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verificación 2FA - Atardecer 8-bit</title>
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
<style>
  :root{--pb:#ffd166;--pg:rgba(255,150,50,.35);--pi:rgba(255,225,150,.16);--ph:#7a1a0a}
  *{box-sizing:border-box;margin:0;padding:0}
  html,body{width:100%;height:100%;overflow:hidden;background:#2a0804;font-family:'Press Start 2P','Courier New',monospace}
  canvas{position:fixed;inset:0;width:100%;height:100%;display:block;image-rendering:pixelated;image-rendering:crisp-edges}
  #scanlines{position:fixed;inset:0;pointer-events:none;z-index:2;background:repeating-linear-gradient(0deg,rgba(0,0,0,.14) 0 1px,transparent 1px 3px);mix-blend-mode:multiply}
  #vignette{position:fixed;inset:0;pointer-events:none;z-index:3;background:radial-gradient(ellipse at center,transparent 55%,rgba(20,2,0,.55) 100%)}
  #login{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10;width:min(360px,92vw);padding:28px 26px 26px;background:linear-gradient(180deg,rgba(28,10,6,.42),rgba(16,6,3,.58));border:3px solid var(--pb);box-shadow:0 0 0 4px rgba(122,26,10,.6),0 0 26px var(--pg),inset 0 5px 0 var(--pi),inset 0 -6px 0 rgba(0,0,0,.35);text-align:center}
  #login h1{font-size:20px;color:var(--pb);text-shadow:3px 3px 0 var(--ph);letter-spacing:2px;margin-bottom:8px}
  #login .sub{font-size:8px;color:#ffb46a;letter-spacing:1px;line-height:1.8;margin-bottom:18px;text-transform:none}
  #login form{display:flex;flex-direction:column;gap:8px;text-align:left}
  #login label{font-size:10px;color:#ffb46a;letter-spacing:1px;margin-top:8px}
  #login input[type="text"]{width:100%;background:rgba(12,5,2,.66);border:2px solid rgba(255,180,90,.55);color:#ffe9b0;font-family:inherit;font-size:11px;padding:11px 10px;outline:none;text-align:center;letter-spacing:1px}
  #login input[type="text"]:focus{border-color:#ffd166;box-shadow:0 0 0 2px rgba(255,180,60,.25)}
  #login input[type="text"]::placeholder{color:rgba(255,180,90,.4);text-transform:none}
  #login button{margin-top:20px;background:#b4412a;border:0;border-bottom:5px solid #57160c;color:#ffe9b0;font-family:inherit;font-size:13px;letter-spacing:2px;padding:13px;cursor:pointer;transition:filter .1s}
  #login button:hover{filter:brightness(1.2)}
  #login button:active{border-bottom-width:2px;transform:translateY(3px)}
  #login button:disabled{opacity:.5;cursor:not-allowed}
  #remember{display:flex;align-items:center;gap:10px;margin-top:14px;cursor:pointer;user-select:none}
  #remember input{width:16px;height:16px;appearance:none;-webkit-appearance:none;background:rgba(12,5,2,.66);border:2px solid rgba(255,180,90,.55);cursor:pointer;position:relative;flex:none}
  #remember input:checked{background:#b4412a;border-color:#ffd166}
  #remember input:checked::after{content:'';position:absolute;left:3px;top:-1px;width:6px;height:10px;border:solid #ffe9b0;border-width:0 3px 3px 0;transform:rotate(45deg)}
  #remember span{font-size:8px;color:#ffb46a;letter-spacing:1px}
  #resend{margin-top:18px}
  #resend button{all:unset;font-size:8px;color:#ffb46a;letter-spacing:1px;cursor:pointer;text-decoration:underline}
  #resend button:hover{color:#ffd166}
  #error{display:block;margin-top:14px;font-size:8px;line-height:1.7;color:#ff9d7a;background:rgba(122,26,10,.4);border:2px solid rgba(255,120,60,.5);padding:8px 10px;text-transform:none}
  #success{display:block;margin-top:14px;font-size:8px;line-height:1.7;color:#9dffb0;background:rgba(16,60,22,.4);border:2px solid rgba(80,200,120,.5);padding:8px 10px;text-transform:none}
  #hint{position:fixed;bottom:10px;left:0;right:0;text-align:center;font-size:9px;color:rgba(255,190,120,.5);z-index:4}
  *{cursor:none!important}
  #crosshair{position:fixed;left:-100px;top:-100px;width:16px;height:16px;z-index:30;pointer-events:none;filter:drop-shadow(0 0 2px rgba(0,0,0,.6))}
  #crosshair::before{content:'';position:absolute;left:7px;top:0;width:2px;height:16px;background:#ff2d2d;box-shadow:0 0 0 1px rgba(255,255,255,.55)}
  #crosshair::after{content:'';position:absolute;top:7px;left:0;height:2px;width:16px;background:#ff2d2d;box-shadow:0 0 0 1px rgba(255,255,255,.55)}
  #crosshair i{position:absolute;left:5px;top:5px;width:6px;height:6px;background:#ff2d2d;box-shadow:0 0 0 1px rgba(255,255,255,.55)}
  #logout{position:fixed;top:14px;left:14px;z-index:15;background:#b4412a;border:2px solid var(--pb);border-bottom-width:4px;color:#ffe9b0;font-family:inherit;font-size:9px;letter-spacing:1px;padding:10px 12px;cursor:pointer;transition:filter .1s;text-transform:uppercase}
  #logout:hover{filter:brightness(1.2)}
  #logout:active{transform:translateY(2px);border-bottom-width:2px}
</style>
</head>
<body>
<canvas id="c"></canvas>
<div id="scanlines"></div>
<div id="vignette"></div>

<div id="login">
  <h1>VERIFICA</h1>
  <div class="sub">Ingresa el código enviado a tu correo</div>

  @if($emailError ?? false)
    <div id="error">NO SE PUDO ENVIAR EL CÓDIGO A TU CORREO.<br>REVISA TU CONEXIÓN O REENVÍA EL CÓDIGO.</div>
  @endif

  @if(session('resend_success'))
    <div id="success">{{ session('resend_success') }}</div>
  @endif

  <form id="f" method="post" action="{{ route('2fa.verify') }}">
    @csrf
    <label for="code">CÓDIGO DE VERIFICACIÓN</label>
    <input id="code" name="token" type="text" placeholder="pega el codigo aqui" autocomplete="one-time-code" required autofocus>

    <label id="remember" for="remember_check">
      <input type="checkbox" name="remember_device" id="remember_check">
      <span>RECORDAR ESTE EQUIPO (30 DÍAS)</span>
    </label>

    @if($isBlocked ?? false)
      <div id="error">DEMASIADOS INTENTOS FALLIDOS.<br>BLOQUEADO HASTA LAS {{ $blockedUntil?->format('H:i') }}.</div>
    @elseif($errors->any())
      <div id="error">
        @foreach($errors->all() as $err)
          {{ $err }}<br>
        @endforeach
      </div>
    @endif

    <button type="submit" {{ ($isBlocked ?? false) ? 'disabled' : '' }}>ENTRAR</button>
  </form>

  @unless($isBlocked ?? false)
    <div id="resend">
      <form method="post" action="{{ route('2fa.resend') }}">
        @csrf
        <button type="submit">REENVIAR CÓDIGO</button>
      </form>
    </div>
  @endunless
</div>

<button id="logout" type="button" onclick="location.href='{{ route('login') }}'">SALIR</button>

<div id="hint">8-BIT SUNSET &#9670; 2FA</div>

<div id="crosshair"><i></i></div>

<script>
(function(){
  var cv=document.getElementById('c'), ctx=cv.getContext('2d');
  var LH=240, GROUND_Y=205;
  var W=480, t=0, last=performance.now(), dtime=0;

  function resize(){
    var aspect=innerWidth/innerHeight;
    W=Math.max(320,Math.round(LH*aspect));
    cv.width=W; cv.height=LH;
    ctx.imageSmoothingEnabled=false;
  }
  resize();
  addEventListener('resize',resize);

  function clamp(v,a,b){return v<a?a:(v>b?b:v)}
  function lerp(a,b,t){return a+(b-a)*t}
  function hexRgb(h){h=h.replace('#','');var n=parseInt(h.length===3?h.split('').map(function(c){return c+c}).join(''):h,16);return{r:n>>16&255,g:n>>8&255,b:n&255}}
  function rgbHex(r,g,b){return'#'+((1<<24)+(clamp(Math.round(r),0,255)<<16)+(clamp(Math.round(g),0,255)<<8)+clamp(Math.round(b),0,255)).toString(16).slice(1)}
  function mixHex(a,b,t){var A=hexRgb(a),B=hexRgb(b);return rgbHex(lerp(A.r,B.r,t),lerp(A.g,B.g,t),lerp(A.b,B.b,t))}

  function mexicoHour(){
    try{
      var parts=new Intl.DateTimeFormat('en-GB',{timeZone:'America/Mexico_City',hour:'2-digit',hourCycle:'h23'}).formatToParts(new Date());
      for(var i=0;i<parts.length;i++)if(parts[i].type==='hour')return parseInt(parts[i].value,10);
    }catch(e){}
    return new Date().getHours();
  }

  function buildPalette(hour){
    var night=hour<6||hour>=20;
    if(night){
      return{sky:['#020617','#0b1b3d','#14275a','#1d3266'],sun:{disc:'#eef2f8',band:'#dbe3ef',glow1:'rgba(205,220,255,.32)',glow2:'rgba(160,185,255,.18)',glow3:'rgba(120,150,255,.10)'},clouds:[{base:'#1c2540',dark:'#121a2e',light:'#2e3c66'},{base:'#232b4d',dark:'#161c33',light:'#3a4a7a'},{base:'#171e33',dark:'#0e1424',light:'#283258'}],farRock:{base:'#10162e',dark:'#0a0e1e',light:'#1c2647'},nearRock:{base:'#0d1326',dark:'#070b18',light:'#16203a'},ground:['#23304f','#10172b'],details:['#3a4a72','#1a233d','#101a2e'],reflect:'rgba(150,180,255,.12)',panel:{border:'#93a8e0',glow:'rgba(130,160,255,.35)',inner:'rgba(200,215,255,.12)',h:'#0a1030'}};
    }else if(hour<8){
      return{sky:['#2a1650','#7a2a68','#c85a48','#f2a03d'],sun:{disc:'#ffdf8a',band:'#ecc25e',glow1:'rgba(255,180,120,.5)',glow2:'rgba(255,140,90,.28)',glow3:'rgba(255,110,70,.14)'},clouds:[{base:'#5c2a66',dark:'#3a1a44',light:'#8a4a86'},{base:'#4a2058',dark:'#2e1438',light:'#6e3a76'},{base:'#6e2a50',dark:'#481a34',light:'#9a4a70'}],farRock:{base:'#5a2a3a',dark:'#381820',light:'#8a4256'},nearRock:{base:'#46202e',dark:'#2c1420',light:'#6e3244'},ground:['#6e4a2a','#3a2414'],details:['#8a5a34','#4a2c18','#381f10'],reflect:'rgba(255,190,110,.22)',panel:{border:'#ffd166',glow:'rgba(255,150,50,.35)',inner:'rgba(255,225,150,.16)',h:'#5a1638'}};
    }else if(hour<15){
      return{sky:['#1f7fdf','#5aa5ee','#9fd0f7','#fdf0c0'],sun:{disc:'#fff4b8',band:'#ffe08a',glow1:'rgba(255,245,190,.5)',glow2:'rgba(255,225,150,.28)',glow3:'rgba(255,210,120,.14)'},clouds:[{base:'#f0f4fa',dark:'#c6ced8',light:'#ffffff'},{base:'#e4ecf6',dark:'#b8c4d4',light:'#ffffff'},{base:'#f4f0e8',dark:'#c8c0b0',light:'#fffdf8'}],farRock:{base:'#8a6a40',dark:'#5a4428',light:'#b08a58'},nearRock:{base:'#6e5238',dark:'#463424',light:'#966c46'},ground:['#e0b85c','#b0873f'],details:['#e8c46a','#9a6a34','#7a5226'],reflect:'rgba(255,235,160,.25)',panel:{border:'#ffe9a0',glow:'rgba(255,225,140,.4)',inner:'rgba(255,245,200,.18)',h:'#1a5a9a'}};
    }else if(hour<17){
      return{sky:['#3a5fc0','#8a8ae0','#d8a06a','#ffd166'],sun:{disc:'#ffdf8a',band:'#f0c060',glow1:'rgba(255,200,120,.5)',glow2:'rgba(255,160,90,.28)',glow3:'rgba(255,130,70,.14)'},clouds:[{base:'#a0829c',dark:'#6a5470',light:'#d4b0c0'},{base:'#8a7694',dark:'#5a4a66',light:'#c0a4c0'},{base:'#b08a8a',dark:'#78585c',light:'#e0c0ac'}],farRock:{base:'#7a4a3a',dark:'#4a2a24',light:'#a86a4a'},nearRock:{base:'#5e3a2e',dark:'#3a241c',light:'#86523a'},ground:['#c09050','#8a5a30'],details:['#d0a458','#7a4a28','#5c361c'],reflect:'rgba(255,210,130,.22)',panel:{border:'#ffd166',glow:'rgba(255,170,70,.35)',inner:'rgba(255,230,150,.16)',h:'#4a1a3a'}};
    }else{
      return{sky:['#4a0606','#a82e0e','#e8721e','#ffd166'],sun:{disc:'#ffd94d',band:'#f0b93a',glow1:'rgba(255,190,80,.5)',glow2:'rgba(255,150,60,.28)',glow3:'rgba(255,120,40,.14)'},clouds:[{base:'#8a1c14',dark:'#5c100c',light:'#c24a26'},{base:'#5c1030',dark:'#3a0a1e',light:'#8a2a4a'},{base:'#a0410f',dark:'#6e2a08',light:'#e07a2c'},{base:'#463a52',dark:'#2a2236',light:'#6b5a78'},{base:'#6e1414',dark:'#450a0a',light:'#a83b1f'}],farRock:{base:'#6e1810',dark:'#45100a',light:'#a83b1f'},nearRock:{base:'#4a120e',dark:'#300a07',light:'#7a1d14'},ground:['#93311d','#59120a'],details:['#a34a2e','#5c1a10','#43100a'],reflect:'rgba(255,214,110,.22)',panel:{border:'#ffd166',glow:'rgba(255,150,50,.35)',inner:'rgba(255,225,150,.16)',h:'#7a1a0a'}};
    }
  }

  var CLOUD_W=1300, FAR_W=820, NEAR_W=980, GROUND_W=620;
  var clouds=[], farRocks=[], nearRocks=[], gd=[];
  var farOff=0,cloudOff=0,nearOff=0,groundOff=0;
  var HORSE_S=0.42;
  var horse={mode:'run',t:0,lastDust:0,x:60,dir:1,vx:0,moveT:0,animT:0};
  var flashes=[],feathers=[],dust=[];
  var HORSE_IMGS=[],horseReady=false;
  (function(){
    var loaded=0;
    for(var i=1;i<=12;i++){
      (function(n){
        var im=new Image();
        im.onload=function(){loaded++;if(loaded>=12)horseReady=true;};
        im.src='/spirit/'+n+'.png';
        HORSE_IMGS[n]=im;
      })(i);
    }
  })();

  function drawHorseImg(n,cx,feetY,s,flip){
    if(!horseReady||!HORSE_IMGS[n])return;
    var im=HORSE_IMGS[n];
    var dw=Math.round(im.width*s), dh=Math.round(im.height*s);
    ctx.save();
    ctx.imageSmoothingEnabled=false;
    if(flip){ctx.translate(Math.round(cx),Math.round(feetY));ctx.scale(-1,1);ctx.drawImage(im,-Math.round(dw/2),-dh,dw,dh);}
    else{ctx.drawImage(im,Math.round(cx-dw/2),Math.round(feetY-dh),dw,dh);}
    ctx.restore();
  }
  function runFrame(){return (Math.floor(horse.animT*9)%6)+1;}
  var MOVE_SPEED=0.42;
  function horseRange(){return Math.max(40,W/2-70);}
  function horseMaxV(){return horseRange()*MOVE_SPEED;}

  function genCloud(seed){
    var g=(function(seed){return function(){seed|=0;seed=seed+0x6D2B79F5|0;var tt=Math.imul(seed^seed>>>15,1|seed);tt=tt+Math.imul(tt^tt>>>7,61|tt)^tt;return((tt^tt>>>14)>>>0)/4294967296}})(seed*31+7);
    var pals=PAL.clouds;
    var pal=pals[Math.floor(g()*pals.length)];
    var thin=g()<0.25;
    var y=126+g()*60;
    var w=36+g()*52;
    var layers=thin?1:(2+(g()<0.45?1:0));
    var blocks=[];
    for(var i=0;i<layers;i++){
      var ly=y-i*4+(g()*2-1)*2;
      var x=-w/2+(g()*2-1)*8;
      var end=-w/2+w*(0.5+g()*0.45);
      var col=i===layers-1?pal.light:(i===0?pal.dark:pal.base);
      while(x<end){
        var bw=3+Math.floor(g()*5);
        var bh=2+Math.floor(g()*3);
        var hy=ly+(i===0?1:0);
        if(g()<0.8)blocks.push({x:x,y:hy,w:bw,h:bh,c:col});
        x+=bw+(g()<0.3?2+Math.floor(g()*4):0);
      }
    }
    return{y:y,w:w+40,blocks:blocks};
  }
  function genClouds(){
    clouds=[];
    for(var ci=0;ci<6;ci++){var cc=genCloud(ci*7+3);cc.x=ci*CLOUD_W/6+cc.y%61;clouds.push(cc)}
  }
  function genRocks(seed,Ww,minW,maxW,minH,maxH){
    var g=(function(seed){return function(){seed|=0;seed=seed+0x6D2B79F5|0;var tt=Math.imul(seed^seed>>>15,1|seed);tt=tt+Math.imul(tt^tt>>>7,61|tt)^tt;return((tt^tt>>>14)>>>0)/4294967296}})(seed*13+11);
    var rocks=[],x=0;
    while(x<Ww){
      var rw=minW+g()*(maxW-minW);
      var rh=minH+g()*(maxH-minH);
      var rx=x+10+g()*40;
      rocks.push({x:rx,w:rw,h:rh,s:Math.round(rx*13+rh*7)});
      x+=90+g()*120;
    }
    return rocks;
  }
  function genGroundDetails(){
    gd=[];
    var g=(function(seed){return function(){seed|=0;seed=seed+0x6D2B79F5|0;var tt=Math.imul(seed^seed>>>15,1|seed);tt=tt+Math.imul(tt^tt>>>7,61|tt)^tt;return((tt^tt>>>14)>>>0)/4294967296}})(77);
    var details=PAL.details;
    for(var i=0;i<26;i++){
      var x=g()*GROUND_W;
      var y=GROUND_Y+4+g()*(LH-GROUND_Y-10);
      var r=g();
      if(r<0.45)gd.push({x:x,y:y,w:1+Math.floor(g()*2),h:1+Math.floor(g()*2),c:g()<0.5?details[0]:details[1],t:0});
      else if(r<0.75)gd.push({x:x,y:y,w:4+Math.floor(g()*3),h:2+Math.floor(g()*2),c:details[1],t:1});
      else gd.push({x:x,y:y,w:6+Math.floor(g()*6),h:3+Math.floor(g()*3),c:details[2],t:2});
    }
  }

  function drawPlus(x,y,sz,col){
    ctx.fillStyle=col;
    ctx.fillRect(Math.round(x),Math.round(y-sz),2,2*sz+2);
    ctx.fillRect(Math.round(x-sz),Math.round(y),2*sz+2,2);
  }
  function drawStars(x,y,s,a){
    for(var i=0;i<3;i++){
      var an=a+i*2.094;
      drawPlus(x+Math.cos(an)*8*s,y+Math.sin(an)*4*s,2*s,'#ffe9b0');
    }
  }
  function spawnFlash(x,y){flashes.push({x:x,y:y,t:0})}
  function spawnFeathers(x,y){
    for(var i=0;i<10;i++){
      feathers.push({x:x,y:y,vx:Math.random()*120-60,vy:-(40+Math.random()*120),life:0.6+Math.random()*0.5,c:Math.random()<0.5?'#c89a6a':'#8a5a2b'});
    }
  }
  function spawnDust(x,y){
    if(dust.length>40)return;
    dust.push({x:x,y:y,vx:-(40+Math.random()*60),vy:-(6+Math.random()*22),life:0.6+Math.random()*0.4});
  }
  function updateEffects(dt){
    for(var i=flashes.length-1;i>=0;i--){flashes[i].t+=dt;if(flashes[i].t>0.28)flashes.splice(i,1);}
    for(var i=feathers.length-1;i>=0;i--){
      var f=feathers[i];f.life-=dt;f.x+=f.vx*dt;f.y+=f.vy*dt;f.vy+=160*dt;
      if(f.life<=0)feathers.splice(i,1);
    }
  }
  function updateDust(){
    for(var i=dust.length-1;i>=0;i--){
      var d=dust[i];
      d.life-=dtime;d.x+=d.vx*dtime;d.y+=d.vy*dtime;d.vy+=40*dtime;
      if(d.life<=0)dust.splice(i,1);
    }
  }
  function drawEffects(){
    for(var i=0;i<flashes.length;i++){
      var fl=flashes[i],p=fl.t/0.28,a=1-p;
      ctx.fillStyle='rgba(255,220,120,'+a.toFixed(2)+')';
      ctx.fillRect(Math.round(fl.x),Math.round(fl.y-2-4*p),2,2+8*p);
      ctx.fillRect(Math.round(fl.x-2-4*p),Math.round(fl.y),2+8*p,2);
    }
    for(var i=0;i<feathers.length;i++){
      var f=feathers[i],a=Math.max(0,f.life/0.6).toFixed(2);
      ctx.fillStyle='rgba(255,230,190,'+a+')';
      ctx.fillRect(Math.round(f.x),Math.round(f.y),2,2);
      ctx.fillStyle='rgba(60,40,25,'+a+')';
      ctx.fillRect(Math.round(f.x+2),Math.round(f.y-1),2,2);
    }
  }
  function drawHorseNew(){
    var s=HORSE_S,g=GROUND_Y+4,cx=horse.x;
    var flip=horse.dir<0;
    ctx.fillStyle='rgba(0,0,0,.35)';
    ctx.beginPath();ctx.ellipse(cx,g+3,120*s,8*s,0,0,6.283);ctx.fill();
    if(horse.mode==='run'){
      drawHorseImg(runFrame(),cx,g,s,flip);
      if(horse.animT-horse.lastDust>0.12){horse.lastDust=horse.animT;spawnDust(cx+4,g);}
    }else if(horse.mode==='fall'){
      var p=Math.min(1,horse.fallT/0.9);
      var fi=7+Math.min(5,Math.floor(p*6));
      drawHorseImg(fi,cx,g,s,flip);
    }else if(horse.mode==='down'){
      drawHorseImg(12,cx,g,s,flip);
      if(horse.downT<2.2)drawStars(cx+6,g-120*s,s,horse.downT*4);
    }else if(horse.mode==='up'){
      var p=Math.min(1,horse.upT/0.7);
      var seq=[10,8,7];
      var fi=seq[Math.min(2,Math.floor(p*3))];
      drawHorseImg(fi,cx,g,s,flip);
    }
  }

  function pixCircle(cx,cy,r,fill,step){
    step=step||2;
    for(var y=-r;y<=r;y+=step){
      var d=Math.sqrt(Math.max(0,r*r-y*y));
      ctx.fillStyle=fill;
      ctx.fillRect(cx-d,cy+y,d*2,step);
    }
  }
  function drawRock(X,r,baseY,col){
    var g=(function(seed){return function(){seed|=0;seed=seed+0x6D2B79F5|0;var tt=Math.imul(seed^seed>>>15,1|seed);tt=tt+Math.imul(tt^tt>>>7,61|tt)^tt;return((tt^tt>>>14)>>>0)/4294967296}})(r.s);
    var cols=Math.max(3,Math.round(r.w/2));
    var hs=[];
    for(var c=0;c<cols;c++)hs.push(r.h*(0.55+g()*0.5));
    for(var c=0;c<cols;c++){
      var hh=Math.round(hs[c]);
      if(c===0||c===cols-1)hh=Math.max(2,Math.round(hh*0.35));
      var x=Math.round(X)+c*2;
      var top=Math.round(baseY-hh);
      ctx.fillStyle=c<cols*0.3?col.dark:col.base;
      ctx.fillRect(x,top,2,hh);
      ctx.fillStyle=col.light;
      ctx.fillRect(x,top,2,1);
    }
  }
  function drawRocks(rocks,Ww,off,baseY,col){
    for(var i=0;i<rocks.length;i++){
      var r=rocks[i];
      var px=((r.x-off)%Ww+Ww)%Ww;
      for(var k=-Ww;k<=Ww;k+=Ww){
        var X=px+k;
        if(X<-r.w||X>W)continue;
        drawRock(X,r,baseY,col);
      }
    }
  }
  function drawCloud(c){
    var px=((c.x-cloudOff)%CLOUD_W+CLOUD_W)%CLOUD_W;
    for(var k=-CLOUD_W;k<=CLOUD_W;k+=CLOUD_W){
      var X=px+k;
      if(X<-c.w||X>W+30)continue;
      for(var b=0;b<c.blocks.length;b++){
        ctx.fillStyle=c.blocks[b].c;
        ctx.fillRect(Math.round(X+c.blocks[b].x),Math.round(c.blocks[b].y),c.blocks[b].w,c.blocks[b].h);
      }
    }
  }
  function drawGroundDetails(){
    for(var i=0;i<gd.length;i++){
      var d=gd[i];
      var px=((d.x-groundOff)%GROUND_W+GROUND_W)%GROUND_W;
      for(var k=-GROUND_W;k<=GROUND_W;k+=GROUND_W){
        var X=px+k;
        if(X<-20||X>W+20)continue;
        var x0=Math.round(X),y0=Math.round(d.y);
        if(d.t===2){
          ctx.fillStyle=d.c;
          ctx.fillRect(x0,y0,2,d.h);
          ctx.fillRect(x0+3,y0+1,2,d.h-1);
          ctx.fillRect(x0+1,y0-1,2,d.h+1);
        }else{
          ctx.fillStyle=d.c;
          ctx.fillRect(x0,y0,d.w,d.h);
        }
      }
    }
  }
  function draw(){
    ctx.imageSmoothingEnabled=false;
    var sky=ctx.createLinearGradient(0,0,0,GROUND_Y);
    sky.addColorStop(0,PAL.sky[0]);
    sky.addColorStop(0.55,PAL.sky[1]);
    sky.addColorStop(0.82,PAL.sky[2]);
    sky.addColorStop(1,PAL.sky[3]);
    ctx.fillStyle=sky;ctx.fillRect(0,0,W,GROUND_Y);
    ctx.fillStyle=PAL.reflect;
    ctx.fillRect(0,GROUND_Y-24,W,24);
    drawRocks(farRocks,FAR_W,farOff,GROUND_Y+1,PAL.farRock);
    var cx=W/2,cy=GROUND_Y+22,r=88;
    pixCircle(cx,cy,r+4,PAL.sun.glow1);
    pixCircle(cx,cy,r+11,PAL.sun.glow2);
    pixCircle(cx,cy,r+19,PAL.sun.glow3);
    pixCircle(cx,cy,r,PAL.sun.disc);
    ctx.save();
    ctx.beginPath();ctx.arc(cx,cy,r,0,6.283);ctx.clip();
    ctx.fillStyle=PAL.sun.band;
    ctx.fillRect(cx-r,cy+14,2*r,5);
    ctx.fillRect(cx-r,cy+26,2*r,4);
    ctx.fillRect(cx-r,cy+38,2*r,3);
    ctx.restore();
    for(var i=0;i<clouds.length;i++)drawCloud(clouds[i]);
    drawRocks(nearRocks,NEAR_W,nearOff,GROUND_Y+3,PAL.nearRock);
    var grd=ctx.createLinearGradient(0,GROUND_Y,0,LH);
    grd.addColorStop(0,PAL.ground[0]);
    grd.addColorStop(1,PAL.ground[1]);
    ctx.fillStyle=grd;ctx.fillRect(0,GROUND_Y,W,LH-GROUND_Y);
    drawGroundDetails();
    for(var j=0;j<dust.length;j++){
      var d=dust[j];
      ctx.fillStyle='rgba(255,190,120,'+Math.max(0,d.life)+')';
      ctx.fillRect(Math.round(d.x),Math.round(d.y),2,2);
    }
    drawEffects();
    drawHorseNew();
  }
  function frame(now){
    var dt=Math.min(0.05,(now-last)/1000);
    last=now;dtime=dt;t+=dt;
    if(horse.mode==='run'){
      var prevX=horse.x;
      horse.moveT+=dt;
      var range=horseRange(),cx0=W/2;
      horse.x=cx0+range*Math.sin(horse.moveT*MOVE_SPEED);
      horse.vx=dt>0?(horse.x-prevX)/dt:0;
      horse.dir=horse.vx>=0?1:-1;
      var spd=Math.min(1,Math.abs(horse.vx)/horseMaxV());
      horse.animT+=dt*(0.25+0.75*spd);
    }else{horse.vx=0;}
    var scroll=(horse.mode==='run'&&horseMaxV()>0)?(horse.vx/horseMaxV()):0;
    farOff+=dt*18*scroll;
    cloudOff+=dt*40*scroll;
    nearOff+=dt*46*scroll;
    groundOff+=dt*95*scroll;
    updateDust();
    updateEffects(dt);
    horse.t+=dt;
    if(horse.mode==='fall'){horse.fallT+=dt;if(horse.fallT>=0.9){horse.mode='down';horse.downT=0;}}
    else if(horse.mode==='down'){horse.downT+=dt;if(horse.downT>=2.7){horse.mode='up';horse.upT=0;}}
    else if(horse.mode==='up'){horse.upT+=dt;if(horse.upT>=0.7){horse.mode='run';}}
    draw();
    requestAnimationFrame(frame);
  }

  var PAL;
  function applyPalette(d){
    PAL=d;
    genClouds();
    genGroundDetails();
    var s=document.documentElement.style;
    s.setProperty('--pb',d.panel.border);
    s.setProperty('--pg',d.panel.glow);
    s.setProperty('--pi',d.panel.inner);
    s.setProperty('--ph',d.panel.h);
  }

  function currentWeather(){var h=mexicoHour();return buildPalette(h);}
  applyPalette(currentWeather());

  var crosshair=document.getElementById('crosshair');
  document.addEventListener('mousemove',function(e){
    crosshair.style.left=(e.clientX-8)+'px';
    crosshair.style.top=(e.clientY-8)+'px';
  });

  document.addEventListener('click',function(e){
    var t=e.target;
    if(t&&t.closest&&t.closest('#login'))return;
    var rect=cv.getBoundingClientRect();
    var mx=(e.clientX-rect.left)/rect.width*W;
    var my=(e.clientY-rect.top)/rect.height*LH;
    spawnFlash(mx,my);
    if(horse.mode==='run'){
      var s=HORSE_S,hx=horse.x,hw=227*s,hh=172*s,hy=GROUND_Y+4-hh/2;
      if(Math.abs(mx-hx)<hw/2+6&&Math.abs(my-hy)<hh/2+6){
        horse.mode='fall';horse.fallT=0;
        spawnFeathers(hx,hy);
      }
    }
  });

  // Efecto de sonido corto al enviar
  var form=document.getElementById('f');
  if(form){
    var sent=false;
    form.addEventListener('submit',function(e){
      if(sent)return;
      e.preventDefault();
      sent=true;
      try{
        var AC=window.AudioContext||window.webkitAudioContext;
        if(AC){
          var actx=new AC();
          var o=actx.createOscillator(),g=actx.createGain();
          o.type='square';o.frequency.setValueAtTime(880,actx.currentTime);
          o.frequency.exponentialRampToValueAtTime(440,actx.currentTime+0.15);
          g.gain.setValueAtTime(0.05,actx.currentTime);
          g.gain.exponentialRampToValueAtTime(0.0001,actx.currentTime+0.2);
          o.connect(g);g.connect(actx.destination);
          o.start();o.stop(actx.currentTime+0.25);
        }
      }catch(e){}
      var btn=form.querySelector('button[type="submit"]');
      if(btn){btn.disabled=true;btn.textContent='VERIFICANDO...';}
      setTimeout(function(){form.submit();},400);
    });
  }

  requestAnimationFrame(frame);
})();
</script>
</body>
</html>