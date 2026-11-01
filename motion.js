/* Local GSAP + ScrollTrigger. Native scroll and full image compositions are preserved. */
(()=>{
 if(!window.gsap||!window.ScrollTrigger)return;
 gsap.registerPlugin(ScrollTrigger);
 // Mobile browser chrome changes height while scrolling; keep trigger positions stable.
 ScrollTrigger.config({ignoreMobileResize:true});
 const mm=gsap.matchMedia();
 const reduced=matchMedia('(prefers-reduced-motion: reduce)');
 let preference=null;
 try{preference=sessionStorage.getItem('charlot-motion')}catch(e){}
 const control=document.createElement('button');control.type='button';control.className='motion-preference';
 document.querySelector('.footer-bottom')?.append(control);
 const tr=document.documentElement.lang==='tr';
 function configure(){
  mm.revert();
  const enabled=preference==='on'||(preference!=='off'&&!reduced.matches);
  control.textContent=enabled?(tr?'Animasyonları duraklat':'Pause animations'):(tr?'Animasyonları aç':'Enable animations');
  control.setAttribute('aria-pressed',String(enabled));
  if(enabled)mm.add('all',animate);
 }
 control.addEventListener('click',()=>{preference=control.getAttribute('aria-pressed')==='true'?'off':'on';try{sessionStorage.setItem('charlot-motion',preference)}catch(e){}configure()});
 if(reduced.addEventListener)reduced.addEventListener('change',configure);else reduced.addListener(configure);
 function animate(){
  let active=true;
  const compact=matchMedia('(max-width: 760px)').matches;
  const distance=compact?16:24;
  const progress=document.createElement('div');progress.className='reading-progress';progress.setAttribute('aria-hidden','true');document.body.append(progress);
  gsap.fromTo(progress,{scaleX:0},{scaleX:1,ease:'none',scrollTrigger:{trigger:document.documentElement,start:0,end:'max',scrub:.15}});
  const intro=gsap.timeline({delay:document.querySelector('.brand-reveal')?1.35:0,defaults:{ease:'power3.out',duration:1}});
  const entrance=(selector,vars,at)=>{const els=document.querySelectorAll(selector);if(els.length)intro.from(els,vars,at)};
  entrance('header .logo',{opacity:0,y:-8,duration:.65},0);
  entrance('header nav>a',{opacity:0,y:-8,stagger:.045,duration:.6},.08);
  entrance('.hero-top .label,.page-intro .label,.about-opening .label,.breadcrumb',{opacity:0,y:12,duration:.7},.12);
  entrance('h1',{opacity:0,y:30,duration:1.15},.24);
  entrance('.hero-top p,.page-intro>p,.about-lead',{opacity:0,y:18,duration:.9},.42);
  entrance('.hero>.hero-image,.about-opening-photo',{opacity:0,duration:1.2},.4);
  entrance('.home-frame',{opacity:0,y:distance,stagger:compact ? .12 : .16,duration:.9,clearProps:'transform,opacity'},.48);
  const cleanup=[];
  function reveal(el,offset=22){
   if(el.closest('.hero-top,.page-intro,.home-contact-sheet')||el.closest('[hidden]'))return;
   gsap.from(el,{y:compact?16:offset,opacity:0,duration:compact ? .65 : .85,ease:'power2.out',scrollTrigger:{trigger:el,start:'top 94%',once:true},clearProps:'transform,opacity'});
  }
  document.querySelectorAll('.section-head,.intro h2,.intro p,.cta h2,.cta p,.cta>.btn,.destination-row,.service,.faq,.reference-bottom,.full-project>.subgrid,.about-story>div,.about-facts,.approach-grid article,.about-service,.destination-opening-content>p,.city-navigation .label,.city-link-grid>a,.destination-topic-grid article,.journal-card,.home-opening-note').forEach(el=>reveal(el));
  document.querySelectorAll('.project-card,.film-card,.home-motion,.film-feature-poster').forEach(el=>{
   gsap.from(el,{y:distance,opacity:0,duration:compact ? .7 : .9,ease:'power2.out',scrollTrigger:{trigger:el,start:'top 95%',once:true},clearProps:'transform,opacity'});
  });
  document.querySelectorAll('.reference-grid').forEach(grid=>{
   gsap.from(grid.children,{opacity:0,y:16,stagger:.055,duration:.65,ease:'power2.out',scrollTrigger:{trigger:grid,start:'top 95%',once:true},clearProps:'transform,opacity'});
  });
  if(matchMedia('(hover:hover) and (pointer:fine)').matches){
   document.querySelectorAll('.btn,.text-link,.destination-row').forEach(el=>{
    const on=()=>gsap.to(el,{x:3,duration:.25,ease:'power2.out'}),off=()=>gsap.to(el,{x:0,duration:.3});
    el.addEventListener('pointerenter',on);el.addEventListener('pointerleave',off);cleanup.push(()=>{el.removeEventListener('pointerenter',on);el.removeEventListener('pointerleave',off)});
   });
  }
  let refreshFrame;
  const refresh=()=>{cancelAnimationFrame(refreshFrame);refreshFrame=requestAnimationFrame(()=>{if(active)ScrollTrigger.refresh()})};
  const filter=refresh;
  document.querySelectorAll('[data-filter]').forEach(b=>{b.addEventListener('click',filter);cleanup.push(()=>b.removeEventListener('click',filter))});
  const step=()=>requestAnimationFrame(()=>{
   const current=document.querySelector('.form-step:not([hidden])')||document.querySelector('#complete:not([hidden])');
   if(current)gsap.fromTo(current,{opacity:0,y:12},{opacity:1,y:0,duration:.4,ease:'power2.out',clearProps:'transform,opacity'});ScrollTrigger.refresh();
  });
  document.querySelectorAll('#next,#back').forEach(b=>{b.addEventListener('click',step);cleanup.push(()=>b.removeEventListener('click',step))});
  document.querySelectorAll('img').forEach(im=>{if(!im.complete){im.addEventListener('load',refresh,{once:true});cleanup.push(()=>im.removeEventListener('load',refresh))}});
  window.addEventListener('load',refresh,{once:true});
  const resume=()=>{gsap.ticker.wake();refresh();ScrollTrigger.update()};
  window.addEventListener('pageshow',resume);
  const visibility=()=>{if(!document.hidden)resume()};
  document.addEventListener('visibilitychange',visibility);
  const orientation=()=>{const timer=setTimeout(refresh,250);cleanup.push(()=>clearTimeout(timer))};
  window.addEventListener('orientationchange',orientation);
  document.fonts?.ready.then(()=>{if(active)refresh()});
  // The mobile menu changes document height; recalculate when it opens or closes.
  const nav=document.querySelector('#nav');
  const navObserver=nav?new MutationObserver(refresh):null;
  if(nav)navObserver.observe(nav,{attributes:true,attributeFilter:['class']});
  refresh();
  return()=>{active=false;cancelAnimationFrame(refreshFrame);cleanup.forEach(fn=>fn());window.removeEventListener('load',refresh);window.removeEventListener('pageshow',resume);document.removeEventListener('visibilitychange',visibility);window.removeEventListener('orientationchange',orientation);navObserver?.disconnect();progress.remove()};
 }
 configure();
})();
