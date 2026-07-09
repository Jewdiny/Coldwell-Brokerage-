# Brief excerpts — exact implementation source

Verbatim GLSL + Three.js snippets from `ColdwellBanker_WebGL_Design_Prompt.md`. Use these
as the precise implementation for the modules described in `CONTRACT.md`. Clamp UV samples
to `[0.001, 0.999]` where a transition can sample outside the texture.

## Transition fragment shaders (CBShaders.transitions)

All share: `precision highp float; varying vec2 vUv; uniform sampler2D uFromTexture, uToTexture;
uniform float uProgress, uTime; #define PI 3.14159265359`.

### landing  (Scene 0→1)
```glsl
vec2 centered = vUv - 0.5;
float zoom = 1.0 + uProgress * 0.12;
vec2 zoomedUv = centered / zoom + 0.5;
vec4 zoomed = texture2D(uFromTexture, zoomedUv);
float blend = smoothstep(0.4, 1.0, uProgress);
gl_FragColor = mix(zoomed, texture2D(uToTexture, vUv), blend);
```

### gallerySpread  (Scene 1→2)
```glsl
float wipeAxis = vUv.x * 0.5 + (1.0 - vUv.y) * 0.5;
float edge = uProgress * 1.4 - 0.2;
float feather = 0.12;
float mask = smoothstep(edge - feather, edge, wipeAxis);
gl_FragColor = mix(texture2D(uFromTexture, vUv), texture2D(uToTexture, vUv), mask);
```

### nightFalls  (Scene 2→3)  — grain spikes 0.02→0.06→0.03 (engine ramps uGrain)
```glsl
vec4 from = texture2D(uFromTexture, vUv);
float darkFade = 1.0 - smoothstep(0.0, 0.45, uProgress) * 0.92;
vec4 darkened = vec4(from.rgb * darkFade, 1.0);
vec4 to = texture2D(uToTexture, vUv);
float emerge = smoothstep(0.45, 1.0, uProgress);
gl_FragColor = mix(darkened, to, emerge);
```

### dawnBreak  (Scene 3→4)  — engine ramps uExposure 1.0→1.4→1.0 (peak at t=0.5)
```glsl
float sweepY = smoothstep(uProgress * 1.2 - 0.2, uProgress * 1.2, 1.0 - vUv.y);
vec4 from = texture2D(uFromTexture, vUv);
vec4 to   = texture2D(uToTexture, vUv);
vec3 warmEdge = vec3(1.0, 0.94, 0.78) * 1.6;
float edgeGlow = smoothstep(abs(sweepY - 0.5), 0.5, 0.08);
vec4 swept = mix(from, to, sweepY);
swept.rgb = mix(swept.rgb, warmEdge, edgeGlow * 0.4);
gl_FragColor = swept;
```

### river  (Scene 4→5)
```glsl
float waveY = vUv.y + sin(vUv.x * 8.0 + uTime * 2.0) * 0.025 * uProgress;
float wipeX = smoothstep(uProgress * 1.15 - 0.15, uProgress * 1.15, vUv.x);
gl_FragColor = mix(texture2D(uFromTexture, vec2(vUv.x, waveY)), texture2D(uToTexture, vUv), wipeX);
```

### goldenMoment  (Scene 5→6)
```glsl
float dist = distance(vUv, vec2(0.5));
float goldFlash = smoothstep(0.3, 0.0, dist) * sin(uProgress * PI) * 0.6;
vec4 from = texture2D(uFromTexture, vUv);
vec4 to   = texture2D(uToTexture, vUv);
vec4 gold = vec4(0.788, 0.659, 0.298, 1.0);
vec4 blended = mix(from, to, smoothstep(0.3, 0.8, uProgress));
gl_FragColor = mix(blended, gold, goldFlash);
```

### theClose  (Scene 6→7)
```glsl
vec2 centered = vUv - 0.5;
float scaleOut = 1.0 - uProgress * 0.06;
vec2 scaledUv = centered / scaleOut + 0.5;
vec4 from = texture2D(uFromTexture, clamp(scaledUv, 0.001, 0.999));
vec4 to   = texture2D(uToTexture, vUv);
gl_FragColor = mix(from, to, smoothstep(0.2, 0.9, uProgress));
```

## Post pass (CBShaders.post) — applied every frame
1. bloom: `uBloomStrength` 0.12–0.22, higher on gold/light; cheap bright-pass additive glow.
2. film grain: `uGrain` 0.02 cinematic (hash on vUv+uTime, signed).
3. vignette: smoothstep radial, `uVignette` per scene.
4. warm LUT grade: push blacks toward `#1B3C55`, highlights toward `#C9A84C`.
5. `uExposure` multiply (default 1.0; dawnBreak spikes to 1.4).

## Scene snippets (exact intent)

### arrival — parallax + camera
```js
planeFar.position.x  = -mouseX * 0.015;
planeMid.position.x  = -mouseX * 0.030;
planeNear.position.x = -mouseX * 0.055;
camera.position.y = Math.sin(time * 0.0003) * 8;   // slow bob
// scroll 0→12%: camera.position.z 600 → 350
```
Dust: ~3000 THREE.Points, size 1.5, color rgba(201,168,76,0.6), drift x = sin(time*0.12+i)*0.08.
Optional faint GridHelper color rgba(201,168,76,0.08), 400×400, div 20, tilted −15° on X.

### welcome — 4 markers
4 SphereGeometry r=6, gold #C9A84C, at Z −30/−60/−100/−140, bloom billboard behind each.
Enter Y +80→0, stagger 120ms, power3.out. Optional SpotLight upper-right angle 0.3 penumbra 0.8
color #FFF0C8 intensity 1.2 on a thin fog plane at Z=−20. Optional DOM lens flare.

### listings — card arc
6 PlaneGeometry 80×60 in an arc: first (−200,0,0) → last (200,0,0), each +3° on Y and −5 on Z.
Float in Y −30→0, stagger 60ms, power3.out. Arc rotates Y 0→−15° across the section.
Optional price sphere r=4 upper-left of each card.

### legacy — star field (signature)
Primary: THREE.Points 12000 verts sampled on SphereGeometry r=2000; sizes 0.5–3.0; colors 80%
white, 15% cream #F0EBE0, 5% gold #C9A84C; slow Y rot time*0.00008.
Milky Way: 4000 tighter points along a great-circle band, sizes 1.5–4.0, soft gaussian sprite.
Rings (enh): 4 RingGeometry inner40/outer42, gold 25% opacity, radii 80/120/160/200, rot
0.0003/0.0002/0.00015/0.0001. Counters tick (DOM) — handled by existing home.js, not WebGL.

### door — opening doors (signature)
Two BoxGeometry 100×160×8, MeshPhysicalMaterial {color:0x012169, roughness:0.15, metalness:0.4,
envMapIntensity:1.2}. Left pivots from X−50, right from X+50. Handles: CylinderGeometry r1.5 h12,
MeshStandardMaterial color #C9A84C roughness0.1 metalness0.9. Open by localProg:
left.rotation.y → −PI*0.55, right.rotation.y → +PI*0.55 (power2.inOut). Warm PointLight at
(0,0,50) intensity 0→1.5 as doors open.

### communities — map
Terrain (enh): PlaneGeometry 200×150 seg 64×48, vertex Y displaced by Perlin noise mag 8,
MeshStandardMaterial color #1B3C55 15% opacity. 6 pins SphereGeometry r=5 gold at:
GrapeCreek(−60,10,−20) Bentwood(−20,12,−10) CollegeHills(10,8,0) Christoval(60,6,30)
Wall(−40,5,40) LakeNasworthy(30,12,−40); thin Line to a label plane above each. River: a few
CatmullRomCurve3 → TubeGeometry r0.8 segs80 color rgba(31,105,255,0.5), draw-in by localProg.
Camera pulled back Z=800, Y=200, look down ~30°, descends Y 200→80.

### value — reveal
Valuation canvas-texture plane "$487,500" Josefin Sans 700 ~120px gold on navy, scale 0.6→1.0
with soft bloom. Enh: 200 upward gold data particles; vertical gold divider plane pulsing 0.4–0.7.

### connect — warmth (terminal, camera holds)
40 SphereGeometry r 3–12, MeshBasicMaterial colors rgba(201,168,76,0.15)→rgba(255,240,200,0.08),
Z −50…−300, gentle scale pulse (amp 0.05, staggered phase). Enh: slow gold ring halo behind the
closing mark. No camera dolly.

## Cursor
Dot 6px gold instant; Ring 32px 1.5px gold @50% lerp 0.10. Hover CTAs/cards: ring scale 1.6,
dot fades, label "View"/"Open". Dark scenes: border → rgba(255,255,255,0.6).

## Scroll pacing — see CONTRACT.md §4 (authoritative).
