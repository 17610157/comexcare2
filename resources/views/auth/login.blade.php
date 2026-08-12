<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Arcade - Atardecer 8-bit</title>
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
<style>
  :root{--pb:#ffd166;--pg:rgba(255,150,50,.35);--pi:rgba(255,225,150,.16);--ph:#7a1a0a}
  *{box-sizing:border-box;margin:0;padding:0}
  html,body{width:100%;height:100%;overflow:hidden;background:#2a0804;font-family:'Press Start 2P','Courier New',monospace}
  canvas{position:fixed;inset:0;width:100%;height:100%;display:block;image-rendering:pixelated;image-rendering:crisp-edges}
  #scanlines{position:fixed;inset:0;pointer-events:none;z-index:2;background:repeating-linear-gradient(0deg,rgba(0,0,0,.14) 0 1px,transparent 1px 3px);mix-blend-mode:multiply}
  #vignette{position:fixed;inset:0;pointer-events:none;z-index:3;background:radial-gradient(ellipse at center,transparent 55%,rgba(20,2,0,.55) 100%)}
  #login{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10;width:min(340px,92vw);padding:28px 26px 26px;background:linear-gradient(180deg,rgba(28,10,6,.42),rgba(16,6,3,.58));border:3px solid var(--pb);box-shadow:0 0 0 4px rgba(122,26,10,.6),0 0 26px var(--pg),inset 0 5px 0 var(--pi),inset 0 -6px 0 rgba(0,0,0,.35);text-align:center}
  #login h1{font-size:26px;color:var(--pb);text-shadow:3px 3px 0 var(--ph);letter-spacing:2px;margin-bottom:22px}
  #login form{display:flex;flex-direction:column;gap:8px;text-align:left}
  #login label{font-size:10px;color:#ffb46a;letter-spacing:1px;margin-top:8px}
  #login input[type="text"],#login input[type="email"],#login input[type="password"]{width:100%;background:rgba(12,5,2,.66);border:2px solid rgba(255,180,90,.55);color:#ffe9b0;font-family:inherit;font-size:12px;padding:11px 10px;outline:none;text-transform:uppercase}
  #login input:focus{border-color:#ffd166;box-shadow:0 0 0 2px rgba(255,180,60,.25)}
  #login input::placeholder{color:rgba(255,180,90,.4);text-transform:none}
  #login button{margin-top:20px;background:#b4412a;border:0;border-bottom:5px solid #57160c;color:#ffe9b0;font-family:inherit;font-size:13px;letter-spacing:2px;padding:13px;cursor:pointer;transition:filter .1s}
  #login button:hover{filter:brightness(1.2)}
  #login button:active{border-bottom-width:2px;transform:translateY(3px)}
  #remember{display:flex;align-items:center;gap:10px;margin-top:14px;cursor:pointer;user-select:none}
  #remember input{width:16px;height:16px;appearance:none;-webkit-appearance:none;background:rgba(12,5,2,.66);border:2px solid rgba(255,180,90,.55);cursor:pointer;position:relative;flex:none}
  #remember input:checked{background:#b4412a;border-color:#ffd166}
  #remember input:checked::after{content:'';position:absolute;left:3px;top:-1px;width:6px;height:10px;border:solid #ffe9b0;border-width:0 3px 3px 0;transform:rotate(45deg)}
  #remember span{font-size:9px;color:#ffb46a;letter-spacing:1px}
  #error{display:block;margin-top:14px;font-size:9px;line-height:1.7;color:#ff9d7a;background:rgba(122,26,10,.4);border:2px solid rgba(255,120,60,.5);padding:8px 10px;text-transform:none}
  #hint{position:fixed;bottom:10px;left:0;right:0;text-align:center;font-size:9px;color:rgba(255,190,120,.5);z-index:4}
  *{cursor:none!important}
  #crosshair{position:fixed;left:-100px;top:-100px;width:16px;height:16px;z-index:30;pointer-events:none;filter:drop-shadow(0 0 2px rgba(0,0,0,.6))}
  #crosshair::before{content:'';position:absolute;left:7px;top:0;width:2px;height:16px;background:#ff2d2d;box-shadow:0 0 0 1px rgba(255,255,255,.55)}
  #crosshair::after{content:'';position:absolute;top:7px;left:0;height:2px;width:16px;background:#ff2d2d;box-shadow:0 0 0 1px rgba(255,255,255,.55)}
  #crosshair i{position:absolute;left:5px;top:5px;width:6px;height:6px;background:#ff2d2d;box-shadow:0 0 0 1px rgba(255,255,255,.55)}
  #paletteBtn{position:fixed;top:14px;right:14px;z-index:15;background:#b4412a;border:2px solid var(--pb);border-bottom-width:4px;color:#ffe9b0;font-family:inherit;font-size:9px;letter-spacing:1px;padding:10px 12px;cursor:pointer;transition:filter .1s;text-transform:uppercase}
  #paletteBtn:hover{filter:brightness(1.2)}
  #paletteBtn:active{transform:translateY(2px);border-bottom-width:2px}
</style>
</head>
<body>
<canvas id="c"></canvas>
<div id="scanlines"></div>
<div id="vignette"></div>

<div id="login">
  <h1>LOGIN</h1>
  <form id="f" method="post" action="{{ route('login') }}">
    @csrf
    <label for="user">USUARIO</label>
    <input id="user" name="email" type="email" placeholder="usuario" autocomplete="username" value="{{ old('email') }}" required autofocus>
    <label for="pass">CONTRASEÑA</label>
    <input id="pass" name="password" type="password" placeholder="****" autocomplete="current-password" required>

    <label id="remember" for="remember_check">
      <input type="checkbox" name="remember" id="remember_check" {{ old('remember') ? 'checked' : '' }}>
      <span>RECORDARME</span>
    </label>

    @if($errors->any())
      <div id="error">
        @foreach($errors->all() as $err)
          {{ $err === 'auth.failed' ? 'Credenciales incorrectas, verifica tu usuario y contraseña.' : $err }}<br>
        @endforeach
      </div>
    @endif

    <button type="submit">ENTRAR</button>
  </form>
</div>

<div id="hint">8-BIT SUNSET &#9670; INTRO</div>

<button id="paletteBtn" type="button">AUTO</button>

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

  function mulberry(seed){return function(){seed|=0;seed=seed+0x6D2B79F5|0;var tt=Math.imul(seed^seed>>>15,1|seed);tt=tt+Math.imul(tt^tt>>>7,61|tt)^tt;return((tt^tt>>>14)>>>0)/4294967296}}
  function rnd(seed){return mulberry(seed)}

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

  var GRAY_RAIN={base:'#8f9aa8',dark:'#5c6674',light:'#c4cdd6',disc:'#e8ecf0',band:'#d5dbe2'};

  function buildPalette(w){
    var hour=w.hour,temp=w.temp,code=w.code||0,isDay=w.isDay;
    var night=!isDay,base,sun,clouds,farRock,nearRock,ground,details,reflect,panel;

    if(night){
      base=['#020617','#0b1b3d','#14275a','#1d3266'];
      sun={disc:'#eef2f8',band:'#dbe3ef',glow1:'rgba(205,220,255,.32)',glow2:'rgba(160,185,255,.18)',glow3:'rgba(120,150,255,.10)'};
      clouds=[{base:'#1c2540',dark:'#121a2e',light:'#2e3c66'},
              {base:'#232b4d',dark:'#161c33',light:'#3a4a7a'},
              {base:'#171e33',dark:'#0e1424',light:'#283258'}];
      farRock={base:'#10162e',dark:'#0a0e1e',light:'#1c2647'};
      nearRock={base:'#0d1326',dark:'#070b18',light:'#16203a'};
      ground=['#23304f','#10172b'];
      details=['#3a4a72','#1a233d','#101a2e'];
      reflect='rgba(150,180,255,.12)';
      panel={border:'#93a8e0',glow:'rgba(130,160,255,.35)',inner:'rgba(200,215,255,.12)',h:'#0a1030'};
    }else if(hour<8){
      base=['#2a1650','#7a2a68','#c85a48','#f2a03d'];
      sun={disc:'#ffdf8a',band:'#ecc25e',glow1:'rgba(255,180,120,.5)',glow2:'rgba(255,140,90,.28)',glow3:'rgba(255,110,70,.14)'};
      clouds=[{base:'#5c2a66',dark:'#3a1a44',light:'#8a4a86'},
              {base:'#4a2058',dark:'#2e1438',light:'#6e3a76'},
              {base:'#6e2a50',dark:'#481a34',light:'#9a4a70'}];
      farRock={base:'#5a2a3a',dark:'#381820',light:'#8a4256'};
      nearRock={base:'#46202e',dark:'#2c1420',light:'#6e3244'};
      ground=['#6e4a2a','#3a2414'];
      details=['#8a5a34','#4a2c18','#381f10'];
      reflect='rgba(255,190,110,.22)';
      panel={border:'#ffd166',glow:'rgba(255,150,50,.35)',inner:'rgba(255,225,150,.16)',h:'#5a1638'};
    }else if(hour<11){
      base=['#3a7fc9','#6fb1e8','#a8d8f4','#f5e3b0'];
      sun={disc:'#ffe9a0',band:'#f5cf6e',glow1:'rgba(255,220,150,.45)',glow2:'rgba(255,190,110,.26)',glow3:'rgba(255,170,90,.13)'};
      clouds=[{base:'#9ab2d4',dark:'#6e86a8',light:'#d0e2f4'},
              {base:'#a8b8d4',dark:'#7888a8',light:'#e0e8f4'},
              {base:'#8aa0c4',dark:'#60789c',light:'#c0d4ec'}];
      farRock={base:'#7a5a4a',dark:'#4a342c',light:'#a87a5c'};
      nearRock={base:'#5c4238',dark:'#382820',light:'#8a5e4a'};
      ground=['#c89a4a','#8a5a2b'];
      details=['#d0a458','#7a4a28','#5c361c'];
      reflect='rgba(255,220,140,.20)';
      panel={border:'#ffd166',glow:'rgba(255,190,90,.35)',inner:'rgba(255,235,170,.16)',h:'#3a5a8a'};
    }else if(hour<15){
      base=['#1f7fdf','#5aa5ee','#9fd0f7','#fdf0c0'];
      sun={disc:'#fff4b8',band:'#ffe08a',glow1:'rgba(255,245,190,.5)',glow2:'rgba(255,225,150,.28)',glow3:'rgba(255,210,120,.14)'};
      clouds=[{base:'#f0f4fa',dark:'#c6ced8',light:'#ffffff'},
              {base:'#e4ecf6',dark:'#b8c4d4',light:'#ffffff'},
              {base:'#f4f0e8',dark:'#c8c0b0',light:'#fffdf8'}];
      farRock={base:'#8a6a40',dark:'#5a4428',light:'#b08a58'};
      nearRock={base:'#6e5238',dark:'#463424',light:'#966c46'};
      ground=['#e0b85c','#b0873f'];
      details=['#e8c46a','#9a6a34','#7a5226'];
      reflect='rgba(255,235,160,.25)';
      panel={border:'#ffe9a0',glow:'rgba(255,225,140,.4)',inner:'rgba(255,245,200,.18)',h:'#1a5a9a'};
    }else if(hour<17){
      base=['#3a5fc0','#8a8ae0','#d8a06a','#ffd166'];
      sun={disc:'#ffdf8a',band:'#f0c060',glow1:'rgba(255,200,120,.5)',glow2:'rgba(255,160,90,.28)',glow3:'rgba(255,130,70,.14)'};
      clouds=[{base:'#a0829c',dark:'#6a5470',light:'#d4b0c0'},
              {base:'#8a7694',dark:'#5a4a66',light:'#c0a4c0'},
              {base:'#b08a8a',dark:'#78585c',light:'#e0c0ac'}];
      farRock={base:'#7a4a3a',dark:'#4a2a24',light:'#a86a4a'};
      nearRock={base:'#5e3a2e',dark:'#3a241c',light:'#86523a'};
      ground=['#c09050','#8a5a30'];
      details=['#d0a458','#7a4a28','#5c361c'];
      reflect='rgba(255,210,130,.22)';
      panel={border:'#ffd166',glow:'rgba(255,170,70,.35)',inner:'rgba(255,230,150,.16)',h:'#4a1a3a'};
    }else{
      base=['#4a0606','#a82e0e','#e8721e','#ffd166'];
      sun={disc:'#ffd94d',band:'#f0b93a',glow1:'rgba(255,190,80,.5)',glow2:'rgba(255,150,60,.28)',glow3:'rgba(255,120,40,.14)'};
      clouds=CLOUD_PALS;
      farRock=FAR_COL;
      nearRock=NEAR_COL;
      ground=['#93311d','#59120a'];
      details=['#a34a2e','#5c1a10','#43100a'];
      reflect='rgba(255,214,110,.22)';
      panel={border:'#ffd166',glow:'rgba(255,150,50,.35)',inner:'rgba(255,225,150,.16)',h:'#7a1a0a'};
    }

    var gray=0;
    var cold=temp<6?0.55:(temp<12?0.3:0);
    var heavy=[61,63,65,66,67,80,81,82,95,96,99];
    var lightR=[51,53,55,56,57,71,73,75,77,85,86];
    if(heavy.indexOf(code)>=0)gray=Math.max(gray,0.65);
    if(lightR.indexOf(code)>=0)gray=Math.max(gray,0.4);
    if(code===45||code===48)gray=Math.max(gray,0.5);
    if(code===3)gray=Math.max(gray,0.35);
    if(code===2)gray=Math.max(gray,0.18);
    gray=Math.max(gray,cold);

    if(gray>0){
      var t=gray,G=GRAY_RAIN;
      for(var i=0;i<base.length;i++)base[i]=mixHex(base[i],'#8f9aa8',t);
      sun.disc=mixHex(sun.disc,G.disc,t);
      sun.band=mixHex(sun.band,G.band,t);
      for(var ci=0;ci<clouds.length;ci++){
        clouds[ci].base=mixHex(clouds[ci].base,G.base,t);
        clouds[ci].dark=mixHex(clouds[ci].dark,G.dark,t);
        clouds[ci].light=mixHex(clouds[ci].light,G.light,t);
      }
      farRock.base=mixHex(farRock.base,'#6e7886',t);
      farRock.dark=mixHex(farRock.dark,'#454e5a',t);
      farRock.light=mixHex(farRock.light,'#93a0ae',t);
      nearRock.base=mixHex(nearRock.base,'#5c6674',t);
      nearRock.dark=mixHex(nearRock.dark,'#39414c',t);
      nearRock.light=mixHex(nearRock.light,'#7e8996',t);
      ground[0]=mixHex(ground[0],'#7c8590',t);
      ground[1]=mixHex(ground[1],'#4d555e',t);
      for(var di=0;di<details.length;di++)details[di]=mixHex(details[di],'#6a7480',t);
      reflect='rgba(200,205,215,'+(0.2*t).toFixed(3)+')';
      sun.glow1='rgba(220,228,238,'+(0.32*t).toFixed(3)+')';
      sun.glow2='rgba(200,210,225,'+(0.18*t).toFixed(3)+')';
      sun.glow3='rgba(180,190,210,'+(0.10*t).toFixed(3)+')';
      panel.border=mixHex(panel.border,'#aeb7c2',t);
      panel.glow='rgba(180,190,205,'+(0.3*t).toFixed(3)+')';
      panel.inner='rgba(225,230,240,'+(0.1*t).toFixed(3)+')';
      panel.h=mixHex(panel.h,'#3a4350',t);
    }

    return{sky:base,sun:sun,clouds:clouds,farRock:farRock,nearRock:nearRock,ground:ground,details:details,reflect:reflect,panel:panel,night:night,gray:gray};
  }

  var CLOUD_PALS=[
    {base:'#8a1c14',dark:'#5c100c',light:'#c24a26'},
    {base:'#5c1030',dark:'#3a0a1e',light:'#8a2a4a'},
    {base:'#a0410f',dark:'#6e2a08',light:'#e07a2c'},
    {base:'#463a52',dark:'#2a2236',light:'#6b5a78'},
    {base:'#6e1414',dark:'#450a0a',light:'#a83b1f'}
  ];

  var CLOUD_W=1300, clouds=[];
  function genCloud(seed,pals){
    var g=rnd(seed*31+7);
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
  function genClouds(pals){
    clouds=[];
    for(var ci=0;ci<6;ci++){var cc=genCloud(ci*7+3,pals);cc.x=ci*CLOUD_W/6+cc.y%61;clouds.push(cc)}
  }

  var FAR_W=820, NEAR_W=980;
  function genRocks(seed,Ww,minW,maxW,minH,maxH){
    var g=rnd(seed*13+11);
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
  var farRocks=genRocks(11,FAR_W,46,96,16,36);
  var nearRocks=genRocks(23,NEAR_W,60,130,22,46);

  var FAR_COL={base:'#6e1810',dark:'#45100a',light:'#a83b1f'};
  var NEAR_COL={base:'#4a120e',dark:'#300a07',light:'#7a1d14'};

  var GROUND_W=620, gd=[];
  function genGroundDetails(details){
    gd=[];
    var g=rnd(77);
    for(var i=0;i<26;i++){
      var x=g()*GROUND_W;
      var y=GROUND_Y+4+g()*(LH-GROUND_Y-10);
      var r=g();
      if(r<0.45)gd.push({x:x,y:y,w:1+Math.floor(g()*2),h:1+Math.floor(g()*2),c:g()<0.5?details[0]:details[1],t:0});
      else if(r<0.75)gd.push({x:x,y:y,w:4+Math.floor(g()*3),h:2+Math.floor(g()*2),c:details[1],t:1});
      else gd.push({x:x,y:y,w:6+Math.floor(g()*6),h:3+Math.floor(g()*3),c:details[2],t:2});
    }
  }

  var farOff=0,cloudOff=0,nearOff=0,groundOff=0;

  var HORSE_S=0.42;
  var horse={mode:'run',t:0,fallT:0,downT:0,upT:0,lastDust:0,x:60,dir:1,vx:0,moveT:0,animT:0};
  var flashes=[],feathers=[];

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
    if(flip){
      ctx.translate(Math.round(cx),Math.round(feetY));
      ctx.scale(-1,1);
      ctx.drawImage(im,-Math.round(dw/2),-dh,dw,dh);
    }else{
      ctx.drawImage(im,Math.round(cx-dw/2),Math.round(feetY-dh),dw,dh);
    }
    ctx.restore();
  }

  function runFrame(){
    return (Math.floor(horse.animT*9)%6)+1;   // frames 1..6 corriendo
  }

  var MOVE_SPEED=0.42;  // rad/s: controla que tan rapido cruza la pantalla
  function horseRange(){ return Math.max(40, W/2-70); }
  function horseMaxV(){ return horseRange()*MOVE_SPEED; }

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

  function updateEffects(dt){
    for(var i=flashes.length-1;i>=0;i--){flashes[i].t+=dt;if(flashes[i].t>0.28)flashes.splice(i,1);}
    for(var i=feathers.length-1;i>=0;i--){
      var f=feathers[i];f.life-=dt;f.x+=f.vx*dt;f.y+=f.vy*dt;f.vy+=160*dt;
      if(f.life<=0)feathers.splice(i,1);
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
      // frames 7..12 mientras cae (secuencia de derribo)
      var p=Math.min(1,horse.fallT/0.9);
      var fi=7+Math.min(5,Math.floor(p*6));
      drawHorseImg(fi,cx,g,s,flip);
    }else if(horse.mode==='down'){
      drawHorseImg(12,cx,g,s,flip); // tumbado en el suelo
      if(horse.downT<2.2)drawStars(cx+6,g-120*s,s,horse.downT*4);
    }else if(horse.mode==='up'){
      // se incorpora: frames 10 -> 8 -> 7 -> corre
      var p=Math.min(1,horse.upT/0.7);
      var seq=[10,8,7];
      var fi=seq[Math.min(2,Math.floor(p*3))];
      drawHorseImg(fi,cx,g,s,flip);
    }
  }

  var dust=[];

  function pixCircle(cx,cy,r,fill,step){
    step=step||2;
    for(var y=-r;y<=r;y+=step){
      var d=Math.sqrt(Math.max(0,r*r-y*y));
      ctx.fillStyle=fill;
      ctx.fillRect(cx-d,cy+y,d*2,step);
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

  function drawRock(X,r,baseY,col){
    var g=rnd(r.s);
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

  function spawnDust(x,y){
    if(dust.length>40)return;
    dust.push({x:x,y:y,vx:-(40+Math.random()*60),vy:-(6+Math.random()*22),life:0.6+Math.random()*0.4});
  }

  function updateDust(){
    for(var i=dust.length-1;i>=0;i--){
      var d=dust[i];
      d.life-=dtime;d.x+=d.vx*dtime;d.y+=d.vy*dtime;d.vy+=40*dtime;
      if(d.life<=0)dust.splice(i,1);
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

    // desplazamiento horizontal del caballo (ida y vuelta con transicion suave en bordes)
    if(horse.mode==='run'){
      var prevX=horse.x;
      horse.moveT+=dt;
      var range=horseRange(), cx0=W/2;
      horse.x=cx0+range*Math.sin(horse.moveT*MOVE_SPEED);
      horse.vx=dt>0?(horse.x-prevX)/dt:0;
      horse.dir=horse.vx>=0?1:-1;
      var spd=Math.min(1,Math.abs(horse.vx)/horseMaxV());
      horse.animT+=dt*(0.25+0.75*spd);
    }else{
      horse.vx=0;
    }

    // el mundo scrollea en direccion opuesta al caballo, proporcional a su velocidad
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

  var crosshair=document.getElementById('crosshair');
  document.addEventListener('mousemove',function(e){
    crosshair.style.left=(e.clientX-8)+'px';
    crosshair.style.top=(e.clientY-8)+'px';
  });

  document.addEventListener('click',function(e){
    var t=e.target;
    if(t&&t.closest&&(t.closest('#login')||t.closest('#paletteBtn')))return;
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

  var PAL;
  function applyPalette(p){
    PAL=p;
    genClouds(p.clouds);
    genGroundDetails(p.details);
    var s=document.documentElement.style;
    s.setProperty('--pb',p.panel.border);
    s.setProperty('--pg',p.panel.glow);
    s.setProperty('--pi',p.panel.inner);
    s.setProperty('--ph',p.panel.h);
  }

  function currentWeather(){
    var h=mexicoHour();
    return{hour:h,temp:20,code:0,isDay:h>=6&&h<20};
  }

  var PAL_MODES=[
    {key:'AUTO',label:'AUTO'},
    {key:'DIA',label:'DIA',w:{hour:13,temp:26,code:0,isDay:true}},
    {key:'NOCHE',label:'NOCHE',w:{hour:23,temp:14,code:0,isDay:false}},
    {key:'LLUVIA',label:'LLUVIA',w:{hour:13,temp:16,code:63,isDay:true}},
    {key:'ATARDECER',label:'ATARDECER',w:{hour:18,temp:22,code:0,isDay:true}}
  ];
  var palMode=0, weatherData=null, palBtn=document.getElementById('paletteBtn');
  function applyPalMode(){
    var m=PAL_MODES[palMode];
    if(m.key==='AUTO'){ applyPalette(buildPalette(weatherData||currentWeather())); }
    else{ applyPalette(buildPalette(m.w)); }
    if(palBtn)palBtn.textContent=m.label;
  }
  if(palBtn){
    palBtn.addEventListener('click',function(){
      palMode=(palMode+1)%PAL_MODES.length;
      applyPalMode();
    });
  }

  applyPalMode();

  fetch('https://api.open-meteo.com/v1/forecast?latitude=19.4326&longitude=-99.1332&current=temperature_2m,is_day,weather_code&timezone=auto&forecast_days=1')
    .then(function(r){if(!r.ok)throw new Error('weather');return r.json()})
    .then(function(d){
      var c=d.current||{};
      var off=(typeof d.utc_offset_seconds==='number')?d.utc_offset_seconds:(-6*3600);
      var mx=new Date(Date.now()+off*1000);
      var hour=isNaN(mx.getTime())?mexicoHour():mx.getUTCHours();
      weatherData={hour:hour,temp:typeof c.temperature_2m==='number'?c.temperature_2m:20,code:c.weather_code||0,isDay:c.is_day===1};
      if(PAL_MODES[palMode].key==='AUTO')applyPalette(buildPalette(weatherData));
    })
    .catch(function(){});
  requestAnimationFrame(frame);
})();
</script>
</body>
</html>
