'use client';

import React, { useEffect, useRef } from 'react';

interface Canvas3DGridProps {
  opacity?: number;
}

export default function Canvas3DGrid({ opacity = 0.85 }: Canvas3DGridProps) {
  const canvasRef = useRef<HTMLCanvasElement>(null);

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    let animationFrameId: number;
    let width = (canvas.width = canvas.parentElement?.clientWidth || window.innerWidth);
    let height = (canvas.height = canvas.parentElement?.clientHeight || window.innerHeight);

    // Handle Resize
    const handleResize = () => {
      if (!canvas) return;
      width = canvas.width = canvas.parentElement?.clientWidth || window.innerWidth;
      height = canvas.height = canvas.parentElement?.clientHeight || window.innerHeight;
    };
    window.addEventListener('resize', handleResize);

    // Grid Parameters
    const cols = 32;
    const rows = 26;
    const spacingX = 55;
    const spacingZ = 35;
    const fov = 350;
    
    let time = 0;

    // Floating Particles
    const particlesCount = 35;
    const particles: Array<{
      x: number;
      y: number;
      z: number;
      vx: number;
      vy: number;
      vz: number;
      size: number;
      alpha: number;
      pulseSpeed: number;
    }> = [];

    for (let i = 0; i < particlesCount; i++) {
      particles.push({
        x: (Math.random() - 0.5) * 1000,
        y: Math.random() * -120 - 40,
        z: Math.random() * 800 + 80,
        vx: (Math.random() - 0.5) * 0.4,
        vy: -Math.random() * 0.4 - 0.15,
        vz: (Math.random() - 0.5) * 0.4,
        size: Math.random() * 1.8 + 0.8,
        alpha: Math.random() * 0.6 + 0.2,
        pulseSpeed: Math.random() * 0.05 + 0.02,
      });
    }

    const draw = () => {
      ctx.clearRect(0, 0, width, height);

      time += 0.012;

      const centerX = width / 2;
      const centerY = height * 0.65; // Vanishing point level

      // 1. Calculate and Project 3D Grid Points
      const points: Array<Array<{ px: number; py: number; depth: number }>> = [];

      for (let r = 0; r < rows; r++) {
        points[r] = [];
        const z = (rows - r) * spacingZ;
        
        for (let c = 0; c < cols; c++) {
          const x = (c - cols / 2) * spacingX;
          
          // Smooth ripples radiating from center
          const dist = Math.sqrt(x * x + z * z);
          const y = Math.sin(dist * 0.006 - time * 1.8) * Math.cos(x * 0.008 + time * 0.5) * 28;

          // Camera parameters (looking down from an elevation)
          const cameraY = -180;
          const relativeY = y - cameraY;

          // Projection
          const scale = fov / (fov + z);
          const px = centerX + x * scale;
          const py = centerY + relativeY * scale;

          points[r][c] = { px, py, depth: z };
        }
      }

      // Draw horizontal lines (latitudes)
      ctx.lineWidth = 1.0;
      for (let r = 0; r < rows; r++) {
        const depthRatio = 1 - (r / rows); // 0 far, 1 close
        ctx.strokeStyle = `rgba(4, 186, 6, ${depthRatio * 0.14})`; // Fade out far lines
        
        ctx.beginPath();
        for (let c = 0; c < cols; c++) {
          const pt = points[r][c];
          if (c === 0) {
            ctx.moveTo(pt.px, pt.py);
          } else {
            ctx.lineTo(pt.px, pt.py);
          }
        }
        ctx.stroke();
      }

      // Draw vertical lines (longitudes)
      for (let c = 0; c < cols; c++) {
        ctx.beginPath();
        for (let r = 0; r < rows - 1; r++) {
          const pt1 = points[r][c];
          const pt2 = points[r + 1][c];
          
          const depthRatio = 1 - (r / rows);
          ctx.strokeStyle = `rgba(4, 186, 6, ${depthRatio * 0.12})`;
          
          ctx.moveTo(pt1.px, pt1.py);
          ctx.lineTo(pt2.px, pt2.py);
        }
        ctx.stroke();
      }

      // 2. Draw 3D Floating Particles (Fairy light / Neon dust effect)
      particles.forEach((p) => {
        // Move particle in 3D Space
        p.x += p.vx;
        p.y += p.vy;
        p.z += p.vz;

        // Reset if it exits our volume bounds
        if (p.y < -350 || p.z < 50 || p.z > 900 || Math.abs(p.x) > 600) {
          p.x = (Math.random() - 0.5) * 900;
          p.y = 20; // reset at grid floor height
          p.z = Math.random() * 800 + 80;
        }

        // Projection
        const scale = fov / (fov + p.z);
        const px = centerX + p.x * scale;
        const py = centerY + (p.y - (-180)) * scale;

        // Depth cue opacity
        const depthRatio = Math.max(0, 1 - (p.z / 900));
        const pulse = Math.abs(Math.sin(time * p.pulseSpeed * 10));
        const alpha = p.alpha * depthRatio * (0.4 + 0.6 * pulse);
        
        ctx.fillStyle = `rgba(4, 186, 6, ${alpha * 0.85})`;
        ctx.beginPath();
        ctx.arc(px, py, p.size * scale * 1.6, 0, Math.PI * 2);
        ctx.fill();

        // Extra ambient green glow for foreground points
        if (p.z < 380) {
          ctx.fillStyle = `rgba(34, 197, 94, ${alpha * 0.25})`;
          ctx.beginPath();
          ctx.arc(px, py, p.size * scale * 4.5, 0, Math.PI * 2);
          ctx.fill();
        }
      });

      animationFrameId = requestAnimationFrame(draw);
    };

    draw();

    return () => {
      window.removeEventListener('resize', handleResize);
      cancelAnimationFrame(animationFrameId);
    };
  }, []);

  return (
    <canvas
      ref={canvasRef}
      className="absolute inset-0 w-full h-full pointer-events-none"
      style={{ opacity }}
    />
  );
}
