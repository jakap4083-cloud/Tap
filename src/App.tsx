import React, { useState, useEffect, useRef } from 'react';
import { 
  Zap, Users, Play, History, User, Coins, Award, Bell, 
  MessageSquare, ChevronRight, X, ArrowUpRight, ArrowDownRight, 
  Settings, Banknote, ShieldCheck, Download, Info, Calendar, 
  Gift, Heart, RefreshCw, Send, CheckCircle2, Lock, AlertTriangle
} from 'lucide-react';

// Design Tokens for Blue & Black Premium Theme
// Primary: #3B82F6 (Blue), Background: #030712 (Deep Black), Surface: #111827 (Charcoal Dark)

// Seed Data representing the SQL Seed precisely
const INITIAL_PRODUCTS = [
  // Ordinary (Biasa)
  { id: 1, category: 'ordinary', name: 'Noxara Basic Miner 1', price: 20000, profit: 1500, dur: 30 },
  { id: 2, category: 'ordinary', name: 'Noxara Basic Miner 2', price: 50000, profit: 4000, dur: 30 },
  { id: 3, category: 'ordinary', name: 'Noxara Basic Miner 3', price: 80000, profit: 6500, dur: 30 },
  { id: 4, category: 'ordinary', name: 'Noxara Basic Miner 4', price: 120000, profit: 10000, dur: 30 },
  { id: 5, category: 'ordinary', name: 'Noxara Basic Miner 5', price: 180000, profit: 16000, dur: 30 },
  // Medium
  { id: 6, category: 'medium', name: 'Noxara Medium Miner 1', price: 250000, profit: 23000, dur: 30 },
  { id: 7, category: 'medium', name: 'Noxara Medium Miner 2', price: 400000, profit: 38000, dur: 30 },
  { id: 8, category: 'medium', name: 'Noxara Medium Miner 3', price: 600000, profit: 60000, dur: 30 },
  { id: 9, category: 'medium', name: 'Noxara Medium Miner 4', price: 850000, profit: 90000, dur: 30 },
  { id: 10, category: 'medium', name: 'Noxara Medium Miner 5', price: 1200000, profit: 132000, dur: 30 },
  // High
  { id: 11, category: 'high', name: 'Noxara High Miner 1', price: 2000000, profit: 230000, dur: 30 },
  { id: 12, category: 'high', name: 'Noxara High Miner 2', price: 3500000, profit: 420000, dur: 30 },
  { id: 13, category: 'high', name: 'Noxara High Miner 3', price: 5000000, profit: 625000, dur: 30 },
  { id: 14, category: 'high', name: 'Noxara High Miner 4', price: 8000000, profit: 1050000, dur: 30 },
  { id: 15, category: 'high', name: 'Noxara High Miner 5', price: 15000000, profit: 2100000, dur: 30 },
];

export default function App() {
  // General App views states
  const [currentTab, setCurrentTab] = useState<'home' | 'team' | 'product' | 'mining' | 'tx' | 'profile'>('home');
  const [activeScreen, setActiveScreen] = useState<'auth' | 'app' | 'loading' | 'forgot'>('auth');
  const [isRegister, setIsRegister] = useState(false);
  const [showWelcome, setShowWelcome] = useState(false);
  const [selectedSubPage, setSelectedSubPage] = useState<string | null>(null);

  // User details & Wallet (Ledger Simulation)
  const [user, setUser] = useState({
    username: 'jaka',
    vip: 0,
    main_balance: 0,
    bonus_balance: 15000, // Rp 15,000 welcome bonus as required
    profit_balance: 0,
    commission_balance: 0,
    locked_balance: 0,
    total_profit: 0,
    total_topup: 0,
    phone: '081234567890',
    email: 'jakap4083@gmail.com',
    bankName: '',
    bankAccount: '',
    bankHolder: '',
    pin: '',
  });

  // Verification & Form Handling
  const [captchaValues, setCaptchaValues] = useState({ q: '5 + 4', ans: 9 });
  const [userInputCaptcha, setUserInputCaptcha] = useState('');
  const [authForm, setAuthForm] = useState({ username: '', email: '', phone: '', password: '', confirm: '', refCode: '' });
  const [tocAgree, setTocAgree] = useState(true);
  const [errorFlash, setErrorFlash] = useState<string | null>(null);
  const [successFlash, setSuccessFlash] = useState<string | null>(null);

  // Active user miner leases
  const [userMiners, setUserMiners] = useState<any[]>([]);
  // Active countdown timers state mapping (id -> {secondsRemaining, isActive})
  const [miningSessions, setMiningSessions] = useState<{ [key: string]: { remaining: number, profit: number } }>({});

  // Cashify Deposit parameters
  const [depositAmount, setDepositAmount] = useState('');
  const [depositMethod, setDepositMethod] = useState('qris');
  const [activeInvoice, setActiveInvoice] = useState<any | null>(null);
  const [checkingPayment, setCheckingPayment] = useState(false);

  // Mini-Games configurations
  const [activeGame, setActiveGame] = useState<'scratch' | 'puzzle' | 'tapcoin' | null>(null);
  const [scratchPercent, setScratchPercent] = useState(0);
  const [scratchReward, setScratchReward] = useState<number | null>(null);
  const [puzzleTiles, setPuzzleTiles] = useState<number[]>([1, 2, 3, 4, 5, 6, 7, 8, 0].sort(() => Math.random() - 0.5));
  const [puzzleTimer, setPuzzleTimer] = useState(60);
  const [fallingCoins, setFallingCoins] = useState<any[]>([]);
  const [tapScore, setTapScore] = useState(0);
  const [tapTimeLeft, setTapTimeLeft] = useState(30);

  // Vouchers state
  const [voucherCode, setVoucherCode] = useState('');

  // Daily claiming checklist
  const [claimedDays, setClaimedDays] = useState<number[]>([]);

  // LEDGER TRANSACTION LIST
  const [ledger, setLedger] = useState<any[]>([
    { id: 'tx-0', type: 'bonus', amount: 15000, desc: 'Bonus Pendaftaran Anggota Baru NOXARA', date: '2026-05-26 18:47:58' }
  ]);

  // Support direct internal chat
  const [chatMessages, setChatMessages] = useState<any[]>([
    { sender: 'admin', message: 'Selamat datang di helpdesk live chat NOXARA. Jika ada kendala, kirimkan pesan di sini!', time: '18:47' }
  ]);
  const [txtMsg, setTxtMsg] = useState('');

  // Admin developer sandbox dashboard parameters for visual simulation
  const [isAdminPanelOpen, setIsAdminPanelOpen] = useState(false);
  const [adminLog, setAdminLog] = useState<string[]>([
    'System Boots Engine PHP Native 8.2 Simulation...',
    'MySQL InnoDB storage engine checking constraints... OK'
  ]);

  // Dynamic Captcha refresh helper
  const refreshCaptcha = () => {
    const num1 = Math.floor(Math.random() * 9) + 1;
    const num2 = Math.floor(Math.random() * 9) + 1;
    const isPlus = Math.random() > 0.5;
    const q = `${num1} ${isPlus ? '+' : '-'} ${num2}`;
    const ans = isPlus ? (num1 + num2) : (num1 - num2);
    setCaptchaValues({ q, ans });
    setUserInputCaptcha('');
  };

  useEffect(() => {
    refreshCaptcha();
  }, [isRegister]);

  // Sound Synthesizer via Web Audio API (satisfies premium gaming / interactive element rules)
  const playBeep = (freq: number = 600, duration: number = 0.1) => {
    try {
      const actx = new (window.AudioContext || (window as any).webkitAudioContext)();
      const osc = actx.createOscillator();
      const gain = actx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(freq, actx.currentTime);
      gain.gain.setValueAtTime(0.1, actx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, actx.currentTime + duration);
      osc.connect(gain);
      gain.connect(actx.destination);
      osc.start();
      osc.stop(actx.currentTime + duration);
    } catch (e) {}
  };

  // Add line logs into developer's visual console
  const logToAdmin = (message: string) => {
    setAdminLog(prev => [`[${new Date().toLocaleTimeString()}] ${message}`, ...prev.slice(0, 20)]);
  };

  // Handle countdown active miners loops
  useEffect(() => {
    const interval = setInterval(() => {
      setMiningSessions(prev => {
        const next: { [key: string]: { remaining: number, profit: number } } = {};
        let changed = false;
        Object.keys(prev).forEach(key => {
          if (prev[key].remaining > 0) {
            next[key] = { remaining: prev[key].remaining - 1, profit: prev[key].profit };
            changed = true;
          }
        });
        return changed ? next : prev;
      });
    }, 1000);
    return () => clearInterval(interval);
  }, []);

  // VIP auto thresholds upgrade monitor based on the accumulated topup
  const recalculateVip = (topupSum: number) => {
    let nextVip = 0;
    if (topupSum >= 1000000) nextVip = 3;
    else if (topupSum >= 100000) nextVip = 2;
    else if (topupSum >= 50000) nextVip = 1;

    if (nextVip > user.vip) {
      setUser(prev => ({ ...prev, vip: nextVip }));
      playBeep(880, 0.4);
      addLedger('vip_upgrade', 0, `Anggota naik menjadi VIP ${nextVip} karena akumulasi topup Rp ${topupSum.toLocaleString()}`);
      logToAdmin(`Account user automatically upgraded to VIP ${nextVip}`);
    }
  };

  // Safe helper to write ledger logs
  const addLedger = (type: string, amount: number, desc: string) => {
    const newTx = {
      id: `tx-${Math.random().toString(36).substr(2, 6)}`,
      type,
      amount,
      desc,
      date: new Date().toISOString().replace('T', ' ').substr(0, 19)
    };
    setLedger(prev => [newTx, ...prev]);
  };

  // Simulation of Authentication routines
  const handleAuth = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorFlash(null);
    setSuccessFlash(null);

    // Validate captcha
    if (parseInt(userInputCaptcha) !== captchaValues.ans) {
      setErrorFlash('Jawaban Captcha Matematika salah!');
      refreshCaptcha();
      return;
    }

    if (!tocAgree) {
      setErrorFlash('Anda wajib menyetujui seluruh ketentuan layanan!');
      return;
    }

    if (isRegister) {
      if (!authForm.username || !authForm.email || !authForm.phone || !authForm.password) {
        setErrorFlash('Semua kolom wajib diisi!');
        return;
      }
      if (authForm.password.length < 8) {
        setErrorFlash('Sandi minimal harus 8 karakter!');
        return;
      }
      if (authForm.password !== authForm.confirm) {
        setErrorFlash('Konfirmasi kata sandi tidak cocok!');
        return;
      }

      setUser(prev => ({
        ...prev,
        username: authForm.username,
        email: authForm.email,
        phone: authForm.phone,
      }));

      logToAdmin(`Sign up successfully created user: ${authForm.username}`);
      setSuccessFlash('Akun berhasil dibuat! Silakan login masuk.');
      setIsRegister(false);
    } else {
      // Simulate login and trigger transitional loading state page
      setActiveScreen('loading');
      playBeep(440, 0.2);
      logToAdmin(`Authenticating login session for user: ${user.username}`);

      setTimeout(() => {
        setActiveScreen('app');
        setShowWelcome(true);
        logToAdmin('Access granted. Mounting UI and checking VIP status.');
      }, 1500);
    }
  };

  // Purchases Products mining system
  const buyProduct = (prod: any) => {
    // Check balances
    const price = prod.price;
    let newBonus = user.bonus_balance;
    let newMain = user.main_balance;
    let spentBonus = 0;
    let spentMain = 0;

    if (newBonus + newMain < price) {
      setErrorFlash('Saldo belanja Anda tidak mencukupi!');
      playBeep(200, 0.3);
      return;
    }

    // Spend bonus first as requested
    if (newBonus >= price) {
      newBonus -= price;
      spentBonus = price;
    } else {
      spentBonus = newBonus;
      newBonus = 0;
      newMain -= (price - spentBonus);
      spentMain = price - spentBonus;
    }

    // Setup user active miner lease
    const leaseId = `miner-${Date.now()}`;
    const newLease = {
      id: leaseId,
      name: prod.name,
      profit: prod.profit,
      durRemaining: prod.dur,
      price: prod.price,
      purchaseDate: new Date().toLocaleDateString()
    };

    setUserMiners(prev => [...prev, newLease]);
    setUser(prev => ({
      ...prev,
      bonus_balance: newBonus,
      main_balance: newMain
    }));

    addLedger('produk', price, `Pembelian ${prod.name} (Saldo Bonus: Rp ${spentBonus}, Saldo Utama: Rp ${spentMain})`);
    setSuccessFlash(`Berhasil menyewa ${prod.name}! Silakan mulai menambang!`);
    playBeep(700, 0.25);
    logToAdmin(`User bought miner id: ${prod.id}. Spent bonus: Rp ${spentBonus}, spent main: Rp ${spentMain}`);
    
    // Auto shift view directly into active Mining rigs tab
    setCurrentTab('mining');
  };

  // Mining session activation countdown
  const startMining = (leaseId: string, profit: number) => {
    // Default 2 hours countdown (we simulate 2 hours as a quick progress countdown of 10s for interactive preview demo)
    setMiningSessions(prev => ({
      ...prev,
      [leaseId]: { remaining: 10, profit: profit }
    }));
    logToAdmin(`Started 2-hour virtual mining cycle for Hardware ${leaseId}`);
    playBeep(520, 0.1);
  };

  // Claim dividends logic
  const claimMiningProfit = (leaseId: string) => {
    const sess = miningSessions[leaseId];
    if (!sess || sess.remaining > 0) return;

    const profit = sess.profit;
    setUser(prev => ({
      ...prev,
      profit_balance: prev.profit_balance + profit,
      total_profit: prev.total_profit + profit
    }));

    // Remove active countdown session
    setMiningSessions(prev => {
      const next = { ...prev };
      delete next[leaseId];
      return next;
    });

    // Reduce duration remaining on miner product
    setUserMiners(prev => prev.map(m => {
      if (m.id === leaseId) {
        return { ...m, durRemaining: Math.max(0, m.durRemaining - 1) };
      }
      return m;
    }));

    addLedger('mining_profit', profit, `Klaim hasil dividen tambang harian sukses`);
    setSuccessFlash(`Sukses klaim profit Rp ${profit.toLocaleString()}! Dana masuk saldo profit.`);
    playBeep(980, 0.2);
    logToAdmin(`Miner ID ${leaseId} successfully distributed digital asset dividend: Rp ${profit}`);
  };

  // Voucher redeem coupons mechanism
  const claimVoucher = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorFlash(null);
    setSuccessFlash(null);

    const code = voucherCode.trim().toUpperCase();
    if (!code) return;

    if (user.vip === 0) {
      setErrorFlash('Akses terbatas! Hanya level VIP 1 Bronze ke atas yang dapat mengeklaim voucher.');
      playBeep(300, 0.25);
      return;
    }

    if (code === 'BONUSTOPUP') {
      setUser(prev => ({ ...prev, main_balance: prev.main_balance + 10000 }));
      addLedger('voucher_claim', 10000, 'Klaim Kode Voucher BONUSTOPUP Sukses');
      setSuccessFlash('Selamat! Anda mendapatkan tambahan saldo utama Rp 10.000!');
      playBeep(880, 0.3);
      logToAdmin('Voucher code "BONUSTOPUP" successfully processed.');
    } else if (code === 'CLAIM5K') {
      setUser(prev => ({ ...prev, main_balance: prev.main_balance + 5000 }));
      addLedger('voucher_claim', 5000, 'Klaim Kode Voucher CLAIM5K Sukses');
      setSuccessFlash('Selamat! Anda mendapatkan Rp 5.000 gratis!');
      playBeep(880, 0.2);
    } else {
      setErrorFlash('Nama Kode Kupon Voucher tidak valid atau kuota habis!');
      playBeep(250, 0.2);
    }
    setVoucherCode('');
  };

  // Cashify Static QRIS Generators Simulation
  const submitDeposit = (val: number) => {
    // Generate unique code difference
    const unique = Math.floor(Math.random() * 900) + 100;
    const finalAmount = val + unique;
    const txId = `CSFY-${Date.now()}`;

    const inv = {
      txId,
      originalAmount: val,
      uniqueCode: unique,
      totalAmount: finalAmount,
      qr_string: `00020101021138510014ID.CASHIFY.WWW011893${finalAmount}5607com.orderkuota.app5802ID`,
      expiresAt: new Date(Date.now() + 15 * 60000).toLocaleTimeString()
    };

    setActiveInvoice(inv);
    playBeep(650, 0.15);
    logToAdmin(`Requested deposit invoice created amount: Rp ${val} via packaging com.orderkuota.app`);
  };

  // Simulate Cashify success confirmation callback
  const verifyPaymentSimulation = () => {
    if (!activeInvoice) return;
    setCheckingPayment(true);
    playBeep(440, 0.1);

    setTimeout(() => {
      // Add balance securely via transaction ledger
      const depTotal = activeInvoice.originalAmount;
      setUser(prev => {
        const nextTopup = prev.total_topup + depTotal;
        recalculateVip(nextTopup);
        return {
          ...prev,
          main_balance: prev.main_balance + depTotal,
          total_topup: nextTopup
        };
      });

      addLedger('topup', depTotal, `Top Up Saldo Sukses via Cashify QRIS (Unique Code: Rp ${activeInvoice.uniqueCode})`);
      setSuccessFlash(`Pembayaran Sukses Terverifikasi! Rp ${depTotal.toLocaleString()} ditambahkan.`);
      logToAdmin(`Resolved payment verification for status invoice: ${activeInvoice.txId}`);
      setActiveInvoice(null);
      setCheckingPayment(false);
      playBeep(1000, 0.3);
    }, 1500);
  };

  // User details bank setups
  const saveBankAccount = (e: React.FormEvent) => {
    e.preventDefault();
    setSuccessFlash('Buku rekening bank berhasil diikat!');
    setSelectedSubPage(null);
    playBeep(600, 0.1);
  };

  const createPIN = (e: React.FormEvent) => {
    e.preventDefault();
    setSuccessFlash('PIN transaksi 6 digit berhasil diaktifkan!');
    setSelectedSubPage(null);
    playBeep(600, 0.1);
  };

  // Support central support ticketing
  const sendSupportMsg = (e: React.FormEvent) => {
    e.preventDefault();
    if (!txtMsg.trim()) return;

    const uMsg = { sender: 'user', message: txtMsg, time: new Date().toLocaleTimeString().substr(0, 5) };
    setChatMessages(prev => [...prev, uMsg]);
    setTxtMsg('');
    playBeep(580, 0.05);

    // Simulated reply after 1.5s
    setTimeout(() => {
      const rMsg = { sender: 'admin', message: 'Terima kasih atas laporan Anda. Keluhan Anda sedang diteliti oleh Customer Service.', time: new Date().toLocaleTimeString().substr(0, 5) };
      setChatMessages(prev => [...prev, rMsg]);
      playBeep(720, 0.08);
    }, 1500);
  };

  // Daily calendar logic
  const claimDailyDay = (dayNum: number, reward: number) => {
    if (claimedDays.includes(dayNum)) return;

    setUser(prev => ({
      ...prev,
      bonus_balance: prev.bonus_balance + reward
    }));

    setClaimedDays(prev => [...prev, dayNum]);
    addLedger('daily_bonus', reward, `Klaim Bonus Kalender Hari ke-${dayNum}`);
    setSuccessFlash(`Klaim Sukses! Ditambahkan Rp ${reward.toLocaleString()} ke saldo belanja.`);
    playBeep(850, 0.15);
  };

  // Scratch card hover scratch simulator
  const handleScratchSim = () => {
    if (scratchPercent >= 100) return;
    setScratchPercent(prev => {
      const next = prev + 15;
      if (next >= 100) {
        // Win reward
        const win = [0, 2000, 5000, 10000][Math.floor(Math.random() * 4)];
        setScratchReward(win);
        if (win > 0) {
          setUser(prevUser => ({ ...prevUser, main_balance: prevUser.main_balance + win }));
          addLedger('game_reward', win, 'Reward Scratch Card Winner');
        }
        playBeep(980, 0.35);
      }
      return next;
    });
  };

  // Slide Timed Puzzle Tiles
  const slideTile = (index: number) => {
    const emptyIndex = puzzleTiles.indexOf(0);
    const validMoves = [
      emptyIndex - 1, emptyIndex + 1, // left right
      emptyIndex - 3, emptyIndex + 3  // top bottom
    ];

    if (validMoves.includes(index)) {
      const nextTiles = [...puzzleTiles];
      nextTiles[emptyIndex] = puzzleTiles[index];
      nextTiles[index] = 0;
      setPuzzleTiles(nextTiles);
      playBeep(450, 0.05);

      // Check win condition [1,2,3,4,5,6,7,8,0]
      if (nextTiles.join(',') === '1,2,3,4,5,6,7,8,0') {
        playBeep(1000, 0.5);
        setUser(prev => ({ ...prev, main_balance: prev.main_balance + 25000 }));
        addLedger('game_reward', 25000, 'Reward Simple Puzzle Solver');
        alert('Luar Biasa! Anda memecahkan puzzle dan meraih bonus Rp 25.000!');
        setActiveGame(null);
      }
    }
  };

  // VIP 3 Catcher Falling Coin Engine
  const startCoinCatcher = () => {
    setTapScore(0);
    setTapTimeLeft(30);
    setFallingCoins([]);
    playBeep(520, 0.2);

    // Spawn coin ticker
    const interval = setInterval(() => {
      setFallingCoins(prev => [
        ...prev,
        {
          id: Math.random(),
          x: Math.floor(Math.random() * 85),
          y: -10,
          val: [100, 250, 500][Math.floor(Math.random() * 3)]
        }
      ]);
    }, 800);

    const timerInt = setInterval(() => {
      setTapTimeLeft(t => {
        if (t <= 1) {
          clearInterval(interval);
          clearInterval(timerInt);
          return 0;
        }
        return t - 1;
      });
    }, 1000);
  };

  // Tap a falling item
  const tapCoinItem = (id: number, val: number) => {
    setTapScore(s => s + val);
    setFallingCoins(prev => prev.filter(c => c.id !== id));
    playBeep(980, 0.05);
  };

  return (
    <div className="min-h-screen bg-slate-950 flex flex-col items-center justify-start py-6 px-4 select-none relative font-sans">
      
      {/* Visual responsive mobile device frame mockup strictly centered */}
      <div className="w-full max-w-[430px] bg-[#030712] rounded-[42px] border-4 border-slate-900 shadow-[0_0_80px_rgba(0,0,0,0.9)] overflow-hidden flex flex-col min-h-[820px] relative pb-20">
        
        {/* Device camera hole */}
        <div className="w-28 h-4 bg-slate-900 absolute top-2 left-1/2 -translate-x-1/2 rounded-full z-50"></div>

        {/* STATIC PREVIEW HEADER */}
        {activeScreen === 'app' && (
          <header className="p-4 pt-8 bg-slate-900/60 border-b border-white/5 flex items-center justify-between sticky top-0 z-40 backdrop-blur-md">
            <div className="flex items-center gap-1.5 pt-1">
              <span className="text-xl font-extrabold text-blue-500 tracking-wider">NOXARA</span>
              <span className="text-[9px] bg-blue-500/10 text-blue-400 border border-blue-500/25 px-1 rounded uppercase font-mono">Mobile v1</span>
            </div>
            
            <div className="flex items-center gap-2 pt-1">
              {/* Fake notification bell info */}
              <button onClick={() => alert('Informasi Status: Pemasangan cron pencadangan and reconciliation backup sukses!')} className="p-1.5 bg-slate-800 rounded-lg hover:bg-slate-700 transition relative">
                <Bell size={15} className="text-slate-300" />
                <span className="absolute top-1 right-1 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
              </button>
              
              <button onClick={() => setSelectedSubPage('live_chat')} className="p-1.5 bg-slate-800 rounded-lg hover:bg-slate-700 transition">
                <MessageSquare size={15} className="text-slate-300" />
              </button>
            </div>
          </header>
        )}

        {/* ERROR / SUCCESS FLASH ANNOUNCEMENTS */}
        {errorFlash && (
          <div className="m-3 p-3 bg-red-500/15 border border-red-500/30 text-red-400 rounded-xl text-xs flex justify-between items-center z-50 animate-pulse">
            <span className="flex items-center gap-1"><AlertTriangle size={13}/> {errorFlash}</span>
            <button onClick={() => setErrorFlash(null)}><X size={14}/></button>
          </div>
        )}
        {successFlash && (
          <div className="m-3 p-3 bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 rounded-xl text-xs flex justify-between items-center z-50">
            <span className="flex items-center gap-1"><CheckCircle2 size={13}/> {successFlash}</span>
            <button onClick={() => setSuccessFlash(null)}><X size={14}/></button>
          </div>
        )}

        {/* MAIN VIEWS CONTROLLER */}
        <div className="flex-grow p-4 overflow-y-auto">
          
          {/* SCREEN 1: LOGIN / SIGN-UP FORM */}
          {activeScreen === 'auth' && (
            <div className="flex flex-col justify-center min-h-[600px] gap-6 py-6 animate-fade-in text-slate-100">
              <div className="text-center">
                <h1 className="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-500 to-cyan-400 tracking-wider">NOXARA</h1>
                <p className="text-slate-400 text-xs mt-1">Platform Multi-Service Penyewaan Miner Digital</p>
              </div>

              <form onSubmit={handleAuth} className="bg-slate-900 border border-slate-800 p-5 rounded-3xl flex flex-col gap-4">
                <h3 className="text-sm font-extrabold uppercase text-blue-500 text-center tracking-widest border-b border-slate-800 pb-2">
                  {isRegister ? 'Registrasi Anggota' : 'Masuk Dashboard'}
                </h3>

                {isRegister ? (
                  <>
                    <div>
                      <label className="text-[10px] uppercase text-slate-400 font-bold block mb-1">Nama Pengguna (Username)</label>
                      <input type="text" required placeholder="User baru" value={authForm.username} onChange={e => setAuthForm({...authForm, username: e.target.value})} className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                    <div>
                      <label className="text-[10px] uppercase text-slate-400 font-bold block mb-1">Email Aktif</label>
                      <input type="email" required placeholder="name@email.com" value={authForm.email} onChange={e => setAuthForm({...authForm, email: e.target.value})} className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                    <div>
                      <label className="text-[10px] uppercase text-slate-400 font-bold block mb-1">Nomor WhatsApp HP</label>
                      <input type="number" required placeholder="08xxxxxxxx" value={authForm.phone} onChange={e => setAuthForm({...authForm, phone: e.target.value})} className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                    <div>
                      <label className="text-[10px] uppercase text-slate-400 font-bold block mb-1">Kata Sandi Baru</label>
                      <input type="password" required placeholder="Min 8 karakter" value={authForm.password} onChange={e => setAuthForm({...authForm, password: e.target.value})} className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                    <div>
                      <label className="text-[10px] uppercase text-slate-400 font-bold block mb-1">Konfirmasi Kata Sandi</label>
                      <input type="password" required placeholder="Ulangi sandi" value={authForm.confirm} onChange={e => setAuthForm({...authForm, confirm: e.target.value})} className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                    <div>
                      <label className="text-[10px] uppercase text-slate-400 font-bold block mb-1">Kode Referral (Opsional)</label>
                      <input type="text" placeholder="Masukkan jika ada" value={authForm.refCode} onChange={e => setAuthForm({...authForm, refCode: e.target.value})} className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                  </>
                ) : (
                  <>
                    <div>
                      <label className="text-[10px] uppercase text-slate-400 font-bold block mb-1">Username / Phone / Email</label>
                      <input type="text" required placeholder="Contoh: jaka" value={user.username} onChange={e => setUser({...user, username: e.target.value})} className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                    <div>
                      <div className="flex justify-between items-center mb-1">
                        <label className="text-[10px] uppercase text-slate-400 font-bold">Kata Sandi Akun</label>
                        <button type="button" onClick={() => setActiveScreen('forgot')} className="text-[10px] text-blue-500 hover:underline">Sandi Gagal?</button>
                      </div>
                      <input type="password" required placeholder="Masukkan kata sandi (Jakakece12)" className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                  </>
                )}

                {/* Mathematical CAPTCHA Code Box */}
                <div className="bg-slate-950 p-3 rounded-xl border border-slate-800 flex justify-between items-center">
                  <span className="text-xs font-mono text-cyan-400">Verifikasi math: {captchaValues.q}</span>
                  <input type="number" required placeholder="Hasil" value={userInputCaptcha} onChange={e => setUserInputCaptcha(e.target.value)} className="w-20 bg-slate-900 border border-slate-800 rounded-lg px-2 py-1 text-center font-mono text-xs text-white" />
                </div>

                <div className="flex items-start gap-2">
                  <input type="checkbox" id="toc" checked={tocAgree} onChange={e => setTocAgree(e.target.checked)} className="mt-1 bg-slate-950 border-slate-800 text-blue-500 rounded focus:ring-0" />
                  <label htmlFor="toc" className="text-[10px] text-slate-400 leading-tight">Saya bersedia menyetujui seluruh dokumen kebijakan privasi ledger & syarat legal layanan NOXARA.</label>
                </div>

                <button type="submit" className="w-full py-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:opacity-90 text-white font-bold text-xs rounded-xl shadow-lg mt-2 tracking-widest uppercase">
                  {isRegister ? 'Selesaikan Registrasi' : 'Koneksikan Akun'}
                </button>
              </form>

              <div className="text-center text-xs">
                {isRegister ? (
                  <span>Sudah memiliki akun? <button onClick={() => setIsRegister(false)} className="text-blue-400 font-bold hover:underline">Masuk Aplikasi</button></span>
                ) : (
                  <span>Pengguna baru? <button onClick={() => setIsRegister(true)} className="text-blue-400 font-bold hover:underline">Registrasi Baru</button></span>
                )}
              </div>

              {/* Promotional metrics footer cards strictly pulling values from the SQL blueprint defaults */}
              <div className="grid grid-cols-2 gap-2 bg-slate-900/40 p-4 border border-slate-900 rounded-2xl text-center">
                <div>
                  <span className="text-[9px] text-slate-400 block">Mitra Aktif Hari Ini</span>
                  <span className="text-sm font-bold text-blue-400">1.205+ User</span>
                </div>
                <div>
                  <span className="text-[9px] text-slate-400 block">Isi Ulang Terproses</span>
                  <span className="text-sm font-bold text-blue-400">Rp 1,28 Milyar</span>
                </div>
              </div>
            </div>
          )}

          {/* SCREEN 2: FORGOT ACCESS GUIDE */}
          {activeScreen === 'forgot' && (
            <div className="py-12 flex flex-col gap-5 text-center animate-fade-in">
              <div className="w-16 h-16 bg-amber-500/10 rounded-full flex items-center justify-center mx-auto border border-amber-500/30 text-amber-500">
                <AlertTriangle size={36}/>
              </div>
              <h3 className="text-lg font-bold text-white">Reset Sandi & PIN Transaksi</h3>
              <p className="text-xs text-slate-400 leading-relaxed px-2">Untuk menjamin keamanan saldo finansial di dalam sistem NOXARA, pemulihan data keamanan dilakukan manual dan diotorisasi penuh oleh tim Customer Service terverifikasi di kantor pusat kami.</p>
              <a href="https://wa.me/6281234567890" target="_blank" className="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 rounded-xl text-xs uppercase tracking-wider flex justify-center items-center gap-2">
                Otorisasi Lewat WhatsApp
              </a>
              <button onClick={() => setActiveScreen('auth')} className="text-slate-500 text-xs hover:underline mt-2">Kembali ke panel masuk</button>
            </div>
          )}

          {/* SCREEN 3: TRANSITIONAL LOADER */}
          {activeScreen === 'loading' && (
            <div className="min-h-[500px] flex flex-col items-center justify-center gap-4 text-center">
              <div className="w-12 h-12 border-3 border-blue-500/20 border-t-blue-500 rounded-full animate-spin"></div>
              <div>
                <h4 className="font-extrabold text-blue-400 text-sm">Menghubungkan ke Server Secure Ledger...</h4>
                <p className="text-[11px] text-slate-400 mt-0.5">Memuat data enkripsi database MySQL 8.2</p>
              </div>
            </div>
          )}

          {/* SCREEN 4: PLATFORM CORE FEATURES */}
          {activeScreen === 'app' && !selectedSubPage && (
            <div className="flex flex-col gap-4 animate-fade-in pb-8">
              
              {/* NEWS MARQUEE ANNOUNCEMENTS TICKER */}
              <div className="bg-blue-500/10 border border-blue-500/20 p-2 rounded-xl flex items-center gap-2 overflow-hidden">
                <span className="text-[8px] font-bold bg-blue-600 text-white px-1.5 py-0.5 rounded shrink-0 uppercase tracking-wider">Info</span>
                <marquee className="text-xs text-blue-300 font-medium" scrollamount="3">
                  Pemberitahuan Resmi: Nikmati pendaftaran gratis modal bonus Rp15.000 untuk pembelian mesin Miner Tier Biasa pertama Anda! Komisi rabat referral 3 tingkat dihitung instan.
                </marquee>
              </div>

              {/* USER ACCREDITED BALANCE CONSOLE */}
              <div className="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col gap-4 relative overflow-hidden">
                <div className="absolute top-0 right-0 w-24 h-24 bg-blue-500/5 rounded-full blur-2xl"></div>
                
                <div className="flex justify-between items-center">
                  <div className="flex items-center gap-2">
                    <div className="w-8 h-8 bg-blue-600/10 border border-blue-600/30 text-blue-500 rounded-full flex items-center justify-center">
                      <User size={15}/>
                    </div>
                    <div>
                      <span className="text-[10px] text-slate-400 block">Status Keanggotaan</span>
                      <span className="text-xs font-bold text-white flex items-center gap-1">
                        {user.username} 
                        <span className="text-[9px] bg-yellow-500/20 text-yellow-500 border border-yellow-500/30 font-extrabold rounded px-1">VIP {user.vip}</span>
                      </span>
                    </div>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4 border-t border-slate-800 pt-4">
                  <div>
                    <span className="text-[9px] text-slate-400 uppercase tracking-widest block">Saldo Utama (WD)</span>
                    <span className="text-lg font-black text-blue-500">Rp {user.main_balance.toLocaleString()}</span>
                  </div>
                  <div>
                    <span className="text-[9px] text-slate-400 uppercase tracking-widest block text-right">Saldo Bonus (Belanja)</span>
                    <span className="text-lg font-black text-cyan-400 text-right block">Rp {user.bonus_balance.toLocaleString()}</span>
                  </div>
                </div>

                {/* Quick actions triggers */}
                <div className="grid grid-cols-2 gap-3 mt-1">
                  <button onClick={() => setSelectedSubPage('topup')} className="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-1">
                    <ArrowUpRight size={14}/> Top Up
                  </button>
                  <button onClick={() => {
                    if (user.main_balance <= 0) {
                      setErrorFlash('Saldo Utama Anda kosong! Tidak dapat melakukan tarik tunai.');
                      playBeep(200, 0.2);
                    } else {
                      setSelectedSubPage('withdraw');
                    }
                  }} className="w-full py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-1">
                    <ArrowDownRight size={14}/> Withdraw
                  </button>
                </div>
              </div>

              {/* WELCOME BANNER CAROUSEL MOCKS */}
              <div className="w-full h-28 bg-gradient-to-r from-slate-900 to-slate-950 p-4 rounded-2xl border border-slate-900 flex flex-col justify-end relative overflow-hidden">
                <div className="absolute top-2 right-2 px-1.5 py-0.5 bg-blue-500 text-white text-[8px] font-mono rounded tracking-wider uppercase font-bold animate-pulse">Event</div>
                <h4 className="text-xs font-black text-white">Event Spesial Upline Rabat:</h4>
                <p className="text-[10px] text-slate-400 leading-tight mt-0.5 max-w-[80%]">Undang rekan Anda bergabung menyewa Miner Tier High 1-5 untuk meraup cashback hingga Rp 2.100.000 cair instan!</p>
              </div>

              {/* TAB CONTROLS RENDERS BASED ON ACTIVE CURRENT TAB */}
              {currentTab === 'home' && (
                <div className="flex flex-col gap-4">
                  {/* GRID 8 MENU BAR WIDGETS */}
                  <div className="grid grid-cols-4 gap-3 bg-slate-900 border border-slate-800/60 p-4 rounded-3xl text-center">
                    <button onClick={() => setSelectedSubPage('vip_ranks')} className="flex flex-col items-center gap-1 hover:opacity-80">
                      <div className="w-10 h-10 bg-yellow-500/10 border border-yellow-500/20 text-yellow-500 rounded-xl flex items-center justify-center">
                        <Award size={20}/>
                      </div>
                      <span className="text-[9px] text-slate-400">Tabel VIP</span>
                    </button>

                    <button onClick={() => setSelectedSubPage('vouchers_screen')} className="flex flex-col items-center gap-1 hover:opacity-80">
                      <div className="w-10 h-10 bg-purple-500/10 border border-purple-500/20 text-purple-400 rounded-xl flex items-center justify-center">
                        <Gift size={20}/>
                      </div>
                      <span className="text-[9px] text-slate-400">Kode Kupon</span>
                    </button>

                    <button onClick={() => setSelectedSubPage('games_hub')} className="flex flex-col items-center gap-1 hover:opacity-80">
                      <div className="w-10 h-10 bg-pink-500/10 border border-pink-500/20 text-pink-400 rounded-xl flex items-center justify-center">
                        <Coins size={20}/>
                      </div>
                      <span className="text-[9px] text-slate-400">VIP Game</span>
                    </button>

                    <button onClick={() => setSelectedSubPage('calendar_screen')} className="flex flex-col items-center gap-1 hover:opacity-80">
                      <div className="w-10 h-10 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center justify-center">
                        <Calendar size={20}/>
                      </div>
                      <span className="text-[9px] text-slate-400">Bonus Claim</span>
                    </button>

                    <a href="https://wa.me/6281234567890" target="_blank" className="flex flex-col items-center gap-1 hover:opacity-80">
                      <div className="w-10 h-10 bg-green-500/10 border border-green-500/20 text-green-400 rounded-xl flex items-center justify-center">
                        <Heart size={20}/>
                      </div>
                      <span className="text-[9px] text-slate-400">Helpline Wa</span>
                    </a>

                    <button onClick={() => setSelectedSubPage('guide_screen')} className="flex flex-col items-center gap-1 hover:opacity-80">
                      <div className="w-10 h-10 bg-blue-500/10 border border-blue-500/20 text-blue-400 rounded-xl flex items-center justify-center">
                        <Info size={20}/>
                      </div>
                      <span className="text-[9px] text-slate-400">Panduan</span>
                    </button>

                    <button onClick={() => setSelectedSubPage('apk_screen')} className="flex flex-col items-center gap-1 hover:opacity-80">
                      <div className="w-10 h-10 bg-red-500/10 border border-red-500/20 text-red-400 rounded-xl flex items-center justify-center">
                        <Download size={20}/>
                      </div>
                      <span className="text-[9px] text-slate-400">Unduh APK</span>
                    </button>

                    <button onClick={() => setSelectedSubPage('promo_screen')} className="flex flex-col items-center gap-1 hover:opacity-80">
                      <div className="w-10 h-10 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 rounded-xl flex items-center justify-center">
                        <Zap size={20}/>
                      </div>
                      <span className="text-[9px] text-slate-400">Promo Event</span>
                    </button>
                  </div>

                  {/* COUNCILS LOGS FOR OPERATIONAL STATISTICS */}
                  <div className="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex flex-col gap-3">
                    <span className="text-[10px] font-extrabold text-blue-500 uppercase tracking-widest block">Audit Publik Platform</span>
                    <div className="grid grid-cols-2 gap-3 text-center">
                      <div className="bg-slate-950 p-2.5 rounded-xl border border-slate-800">
                        <span className="text-[8px] text-slate-400 block uppercase">Total Saldo Sukses</span>
                        <span className="text-xs font-bold text-slate-100">Rp 1.420.750.000</span>
                      </div>
                      <div className="bg-slate-950 p-2.5 rounded-xl border border-slate-800">
                        <span className="text-[8px] text-slate-400 block uppercase">Penarikan Sukses Terbayar</span>
                        <span className="text-xs font-bold text-slate-100">Rp 984.520.000</span>
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* COGNATE VIEW RENDERS */}
              {currentTab === 'team' && (
                <div className="flex flex-col gap-4 animate-fade-in">
                  <div className="bg-slate-900 border border-slate-800 p-5 rounded-2xl flex flex-col gap-4">
                    <h3 className="text-sm font-bold text-blue-500 uppercase border-b border-slate-800 pb-2">Kode Referensi Anda</h3>
                    
                    <div className="flex gap-2">
                      <input type="text" readOnly value={`https://noxara.page/?ref=NOX${user.username.toUpperCase()}`} className="flex-grow bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-300 font-mono" />
                      <button onClick={() => {
                        navigator.clipboard.writeText(`https://noxara.page/?ref=NOX${user.username.toUpperCase()}`);
                        setSuccessFlash('Tautan referral berhasil disalin!');
                      }} className="bg-blue-600 hover:bg-blue-500 text-white px-3 rounded-xl text-xs font-bold">Salin</button>
                    </div>

                    <div className="grid grid-cols-3 gap-2 text-center mt-2">
                      <div className="bg-slate-950 p-2 rounded-xl border border-slate-850">
                        <span className="text-[8px] text-slate-400 block">Level 1 (10%)</span>
                        <span className="text-xs font-bold text-white">0 Anggota</span>
                      </div>
                      <div className="bg-slate-950 p-2 rounded-xl border border-slate-850">
                        <span className="text-[8px] text-slate-400 block">Level 2 (5%)</span>
                        <span className="text-xs font-bold text-white">0 Anggota</span>
                      </div>
                      <div className="bg-slate-950 p-2 rounded-xl border border-slate-850">
                        <span className="text-[8px] text-slate-400 block">Level 3 (2%)</span>
                        <span className="text-xs font-bold text-white">0 Anggota</span>
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {currentTab === 'product' && (
                <div className="flex flex-col gap-4 animate-fade-in">
                  <div className="flex justify-between items-center pb-2 border-b border-slate-800">
                    <h3 className="text-sm font-black text-blue-500 uppercase tracking-widest">Toko Hardware Miner</h3>
                    <span className="text-[10px] bg-cyan-400/10 text-cyan-400 border border-cyan-400/20 px-2 py-0.5 rounded-full font-mono">15 Mesin Tersedia</span>
                  </div>

                  <div className="flex flex-col gap-4">
                    {/* Render Category blocks directly */}
                    {['ordinary', 'medium', 'high'].map(cat => (
                      <div key={cat} className="flex flex-col gap-2.5">
                        <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1 ml-1">{cat === 'ordinary' ? 'ORDINARY (Biasa)' : cat.toUpperCase()} TIER</h4>
                        {INITIAL_PRODUCTS.filter(p => p.category === cat).map(prod => (
                          <div key={prod.id} className="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex justify-between items-center hover:border-slate-700 transition">
                            <div className="flex flex-col gap-1">
                              <span className="text-xs font-bold text-white">{prod.name}</span>
                              <div className="flex gap-2 items-center">
                                <span className="text-[10px] text-blue-400 font-bold">Harga: Rp {prod.price.toLocaleString()}</span>
                                <span className="text-[10px] text-emerald-400">Profit: Rp {prod.profit.toLocaleString()}/hari</span>
                              </div>
                            </div>
                            <button onClick={() => buyProduct(prod)} className="bg-blue-600 hover:bg-blue-500 text-white text-xs px-3 py-1.5 rounded-xl font-bold">Sewa</button>
                          </div>
                        ))}
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {currentTab === 'mining' && (
                <div className="flex flex-col gap-4 animate-fade-in">
                  <div className="pb-2 border-b border-slate-800">
                    <h3 className="text-sm font-black text-blue-500 uppercase">Kontrol Hardware Aktif Anda</h3>
                  </div>

                  {userMiners.length === 0 ? (
                    <div className="bg-slate-900 border border-slate-800/50 p-8 rounded-2xl text-center flex flex-col items-center justify-center gap-3">
                      <Play size={28} className="text-slate-600 animate-pulse" />
                      <div>
                        <span className="text-xs font-bold text-slate-300 block">Belum ada hardware mesin terpasang</span>
                        <p className="text-[10px] text-slate-500 mt-0.5">Mulai menyewa mesin di panel produk (+) untuk memulai dividen pertambangan harian Anda</p>
                      </div>
                    </div>
                  ) : (
                    <div className="flex flex-col gap-3">
                      {userMiners.map(miner => {
                        const sess = miningSessions[miner.id];
                        return (
                          <div key={miner.id} className="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex flex-col gap-3">
                            <div className="flex justify-between items-center">
                              <div>
                                <span className="text-xs font-extrabold text-white">{miner.name}</span>
                                <span className="text-[9px] text-slate-500 block">Sisa Sewa Kontrak: {miner.durRemaining} Hari</span>
                              </div>
                              <span className="text-[10px] bg-slate-950 px-2 py-0.5 rounded border border-slate-800 text-cyan-400 font-mono">Rp {miner.profit.toLocaleString()}/hari</span>
                            </div>

                            {/* State verification display */}
                            {sess ? (
                              <div className="flex flex-col gap-2 bg-slate-950 p-3 rounded-xl border border-slate-850">
                                <div className="flex justify-between text-[10px]">
                                  <span className="text-slate-400">Komputasi Algoritma Tambang...</span>
                                  <span className="text-blue-400 font-mono">{sess.remaining > 0 ? `${sess.remaining} Jam` : 'SELESAI'}</span>
                                </div>
                                <div className="w-full bg-slate-900 h-2 rounded-full overflow-hidden">
                                  <div className="bg-blue-500 h-full transition-all duration-1000" style={{ width: `${((10 - sess.remaining) / 10) * 100}%` }}></div>
                                </div>
                                {sess.remaining === 0 && (
                                  <button onClick={() => claimMiningProfit(miner.id)} className="w-full py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold rounded-lg mt-1 transition shadow-lg">Klaim Dividen Rp {sess.profit.toLocaleString()}</button>
                                )}
                              </div>
                            ) : (
                              <button onClick={() => startMining(miner.id, miner.profit)} className="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl flex items-center justify-center gap-1.5">
                                <Play size={12}/> Mulai Komputasi Pertambangan (2 Jam)
                              </button>
                            )}
                          </div>
                        );
                      })}
                    </div>
                  )}
                </div>
              )}

              {currentTab === 'tx' && (
                <div className="flex flex-col gap-4 animate-fade-in">
                  <div className="pb-2 border-b border-slate-800">
                    <h3 className="text-sm font-black text-blue-500 uppercase">Riwayat Ledger Bookkeeping</h3>
                  </div>

                  <div className="flex flex-col gap-2">
                    {ledger.map(row => (
                      <div key={row.id} className="bg-slate-900 border border-slate-800/80 p-3 rounded-xl flex justify-between items-center">
                        <div className="flex flex-col gap-0.5">
                          <span className="text-[11px] font-bold text-white">{row.desc}</span>
                          <span className="text-[9px] text-slate-500">{row.date}</span>
                        </div>
                        <div className="text-right">
                          <span className="text-xs font-bold text-blue-400 font-mono">+Rp {row.amount.toLocaleString()}</span>
                          <span className="text-[8px] uppercase font-mono block text-slate-400">{row.type}</span>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {currentTab === 'profile' && (
                <div className="flex flex-col gap-4 animate-fade-in">
                  <div className="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex flex-col gap-3 text-center">
                    <div className="w-14 h-14 bg-gradient-to-tr from-blue-600 to-cyan-400 rounded-full flex items-center justify-center text-white text-lg font-black mx-auto">
                      {user.username.substr(0, 1).toUpperCase()}
                    </div>
                    <div>
                      <h4 className="text-xs font-extrabold text-white">{user.username}</h4>
                      <p className="text-[10px] text-slate-400 mt-0.5">{user.email}</p>
                    </div>
                  </div>

                  {/* Security items and bank binds check */}
                  <div className="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col gap-2 text-xs">
                    <button onClick={() => setSelectedSubPage('bank_account')} className="flex justify-between items-center py-2 border-b border-slate-850 hover:opacity-80">
                      <span className="text-slate-300">Konfigurasi Pengikatan Rekening Bank</span>
                      <ChevronRight size={14} className="text-slate-500"/>
                    </button>
                    <button onClick={() => setSelectedSubPage('pin_setup')} className="flex justify-between items-center py-2 border-b border-slate-850 hover:opacity-80">
                      <span className="text-slate-300">Buat PIN Transaksi Penarikan (6 Digit)</span>
                      <ChevronRight size={14} className="text-slate-500"/>
                    </button>
                    <button onClick={() => {
                      if (window.confirm('Apakah Anda yakin ingin logout aman hancurkan sesi?')) {
                        setActiveScreen('auth');
                        logToAdmin('Sesi dihancurkan. Logged out.');
                      }
                    }} className="flex justify-between items-center py-2 text-red-400 font-bold hover:opacity-80">
                      <span>Keluar Aplikasi (Log Out)</span>
                      <ChevronRight size={14}/>
                    </button>
                  </div>
                </div>
              )}

            </div>
          )}

          {/* SCREEN 5: SUB-PAGES MODALS / LAYOUT FLOWS */}

          {/* A. VIP TAB TABLE */}
          {selectedSubPage === 'vip_ranks' && (
            <div className="py-2 flex flex-col gap-4 animate-fade-in text-xs text-slate-300">
              <div className="flex justify-between items-center border-b border-slate-800 pb-2">
                <h3 className="font-bold text-sm text-yellow-500">Katalog Lisensi VIP NOXARA</h3>
                <button onClick={() => setSelectedSubPage(null)}><X size={16}/></button>
              </div>
              <p className="text-[10px] text-slate-400 font-sans">Kalkulasi status VIP dihitung otomatis oleh Nginx VPS backend berdasarkan total deposit sukses, tanpa memotong saldo saat belanja.</p>
              
              <div className="flex flex-col gap-3">
                <div className="bg-slate-900 p-3.5 rounded-xl border border-slate-800 flex justify-between items-center">
                  <div>
                    <h4 className="font-bold text-white text-[11px]">VIP 0 (Level Awal)</h4>
                    <p className="text-[9px] text-slate-400">Total isi ulang sukses: Rp 0 - Rp 49.999</p>
                  </div>
                  <span className="text-[10px] font-bold text-red-400 font-mono">Biaya WD: 10%</span>
                </div>
                <div className="bg-slate-900 p-3.5 rounded-xl border border-slate-800 flex justify-between items-center">
                  <div>
                    <h4 className="font-bold text-white text-[11px]">VIP 1 Bronze</h4>
                    <p className="text-[9px] text-slate-400">Minimal total pengisian: Rp 50.000</p>
                  </div>
                  <span className="text-[10px] font-bold text-blue-400 font-mono">Biaya WD: 5%</span>
                </div>
                <div className="bg-slate-900 p-3.5 rounded-xl border border-slate-800 flex justify-between items-center">
                  <div>
                    <h4 className="font-bold text-white text-[11px]">VIP 2 Silver</h4>
                    <p className="text-[9px] text-slate-400">Minimal total pengisian: Rp 100.000</p>
                  </div>
                  <span className="text-[10px] font-bold text-blue-400 font-mono">Biaya WD: 2.5%</span>
                </div>
                <div className="bg-slate-900 p-3.5 rounded-xl border border-slate-800 flex justify-between items-center">
                  <div>
                    <h4 className="font-bold text-white text-[11px]">VIP 3 Gold</h4>
                    <p className="text-[9px] text-slate-400">Minimal total pengisian: Rp 1.000.000</p>
                  </div>
                  <span className="text-[10px] font-bold text-emerald-400 font-mono">Biaya WD: 0%</span>
                </div>
              </div>
            </div>
          )}

          {/* B. VOUCHERS CLAIM SCREEN */}
          {selectedSubPage === 'vouchers_screen' && (
            <div className="py-2 flex flex-col gap-4 animate-fade-in">
              <div className="flex justify-between items-center border-b border-slate-800 pb-2">
                <h3 className="font-bold text-sm text-purple-400">Klaim Kode Voucher Bonus</h3>
                <button onClick={() => setSelectedSubPage(null)}><X size={16}/></button>
              </div>

              <form onSubmit={claimVoucher} className="bg-slate-900 p-5 rounded-2xl border border-slate-800 flex flex-col gap-3">
                <label className="text-[10px] text-slate-400 uppercase font-mono">Masukkan Kode Voucher Khusus:</label>
                <input type="text" placeholder="Contoh: BONUSTOPUP" value={voucherCode} onChange={e => setVoucherCode(e.target.value)} className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-purple-500 focus:outline-none uppercase font-mono text-white" />
                <button type="submit" className="w-full py-2.5 bg-purple-600 hover:bg-purple-500 rounded-xl text-xs font-bold text-white">Gunakan Voucher</button>
              </form>
            </div>
          )}

          {/* C. VIP MINI GAMES HUB */}
          {selectedSubPage === 'games_hub' && (
            <div className="py-2 flex flex-col gap-4 animate-fade-in text-slate-300">
              <div className="flex justify-between items-center border-b border-slate-800 pb-2">
                <h3 className="font-bold text-sm text-pink-500">Roda VIP Games Reward</h3>
                <button onClick={() => {
                  setSelectedSubPage(null);
                  setActiveGame(null);
                }}><X size={16}/></button>
              </div>

              {user.vip < 1 ? (
                <div className="bg-slate-900 p-6 rounded-2xl border border-slate-800 text-center flex flex-col gap-3">
                  <div className="w-12 h-12 bg-red-500/10 border border-red-500/20 text-red-500 rounded-full flex items-center justify-center mx-auto">
                    <Lock size={20}/>
                  </div>
                  <div>
                    <h4 className="font-bold text-sm">Mini Games Terkunci!</h4>
                    <p className="text-[11px] text-slate-400 mt-0.5 leading-relaxed">Pintu roda game divalidasi server hanya dapat dijangkau oleh Anggota VIP 1 Bronze ke atas. Silakan isi ulang minimal Rp50.000 untuk menaiki VIP otomatis.</p>
                  </div>
                </div>
              ) : (
                <div className="flex flex-col gap-4">
                  {!activeGame ? (
                    <>
                      <p className="text-[10px] text-slate-400">Pilih salah satu game reward instan harian yang aktif berdasarkan kualifikasi VIP tingkat Anda:</p>
                      
                      <div className="grid grid-cols-1 gap-3">
                        <button onClick={() => {
                          setActiveGame('scratch');
                          setScratchPercent(0);
                          setScratchReward(null);
                        }} className="bg-slate-900 border border-slate-805 p-4 rounded-xl flex justify-between items-center text-left hover:border-pink-500 transition">
                          <div>
                            <span className="text-xs font-bold text-white block">VIP 1: Scratch and Win</span>
                            <span className="text-[9px] text-slate-400">Gosok kartu digital untuk hadiah acak</span>
                          </div>
                          <ChevronRight size={14}/>
                        </button>

                        <button onClick={() => {
                          if (user.vip < 2) {
                            alert('Game ini membutuhkan kualifikasi minimal VIP 2!');
                          } else {
                            setActiveGame('puzzle');
                            setPuzzleTiles([1, 2, 3, 4, 5, 6, 7, 8, 0].sort(() => Math.random() - 0.5));
                          }
                        }} className="bg-slate-900 border border-slate-805 p-4 rounded-xl flex justify-between items-center text-left hover:border-pink-500 transition opacity-90">
                          <div>
                            <span className="text-xs font-bold text-white block">VIP 2: Sliding Blocks Puzzle</span>
                            <span className="text-[9px] text-slate-400">Susun potongan puzzle berwaktu</span>
                          </div>
                          <ChevronRight size={14}/>
                        </button>

                        <button onClick={() => {
                          if (user.vip < 3) {
                            alert('Game ini membutuhkan kualifikasi minimal VIP 3!');
                          } else {
                            setActiveGame('tapcoin');
                            startCoinCatcher();
                          }
                        }} className="bg-slate-900 border border-slate-805 p-4 rounded-xl flex justify-between items-center text-left hover:border-pink-500 transition opacity-80">
                          <div>
                            <span className="text-xs font-bold text-white block">VIP 3: Falling Coin Catcher</span>
                            <span className="text-[9px] text-slate-400">Ketuk koin jatuh berhadiah tunai</span>
                          </div>
                          <ChevronRight size={14}/>
                        </button>
                      </div>
                    </>
                  ) : (
                    <div className="bg-slate-900 p-4 rounded-xl border border-slate-800 flex flex-col gap-4">
                      <div className="flex justify-between items-center">
                        <span className="text-xs font-bold uppercase text-pink-500">{activeGame === 'scratch' ? 'Scratch Card' : activeGame === 'puzzle' ? 'Sliding Puzzle' : 'Coin Catcher'}</span>
                        <button onClick={() => setActiveGame(null)} className="text-slate-500 text-xs">Kembali</button>
                      </div>

                      {/* GAME 1: SCRATCH CARD */}
                      {activeGame === 'scratch' && (
                        <div className="flex flex-col gap-4 text-center">
                          <p className="text-[10px] text-slate-400">Seret/Geser mouse/sentuh wadah abu-abu di bawah untuk menggosok dan mendapatkan hadiah.</p>
                          <div onMouseMove={handleScratchSim} className="w-full h-32 bg-slate-950 border border-slate-800 rounded-xl relative overflow-hidden flex items-center justify-center cursor-pointer">
                            {scratchPercent < 100 ? (
                              <div className="absolute inset-0 bg-slate-800 flex items-center justify-center text-xs font-mono text-slate-400 font-bold transition-all" style={{ opacity: 1 - scratchPercent/100 }}>
                                Gosok Di Sini (Sisa: {100 - scratchPercent}%)
                              </div>
                            ) : null}
                            <div className="text-center">
                              {scratchReward !== null ? (
                                <div className="text-slate-100 font-bold">
                                  <span className="text-xs text-slate-400 block">Kupon Hasil:</span>
                                  <span className="text-lg font-black text-emerald-400">Rp {scratchReward.toLocaleString()}</span>
                                  <span className="text-[9px] text-slate-500 block mt-1">Saldo tunai ditambahkan!</span>
                                </div>
                              ) : (
                                <span className="text-xs text-slate-400">Loading...</span>
                              )}
                            </div>
                          </div>
                        </div>
                      )}

                      {/* GAME 2: SLIDING PUZZLE */}
                      {activeGame === 'puzzle' && (
                        <div className="flex flex-col gap-3 text-center">
                          <p className="text-[10px] text-slate-400">Klik kepingan angka yang bersebelahan dengan keping kosong (0) untuk memindahkan.</p>
                          <div className="grid grid-cols-3 gap-1 px-8">
                            {puzzleTiles.map((tile, idx) => (
                              <button key={idx} onClick={() => slideTile(idx)} className={`w-14 h-14 rounded-lg flex items-center justify-center text-xs font-black select-none ${tile === 0 ? 'bg-slate-950 border border-transparent' : 'bg-slate-800 border border-slate-700 text-white hover:bg-slate-700'}`}>
                                {tile !== 0 ? tile : ''}
                              </button>
                            ))}
                          </div>
                        </div>
                      )}

                      {/* GAME 3: FALLING COINS CATCHER */}
                      {activeGame === 'tapcoin' && (
                        <div className="flex flex-col gap-3">
                          <div className="flex justify-between items-center text-[10px] text-slate-400 px-1">
                            <span>Sisa Waktu: {tapTimeLeft}s</span>
                            <span>Score: Rp {tapScore.toLocaleString()}</span>
                          </div>

                          <div className="w-full h-44 bg-slate-950 border border-slate-850 rounded-xl relative overflow-hidden">
                            {fallingCoins.map(coin => (
                              <button key={coin.id} onClick={() => tapCoinItem(coin.id, coin.val)} className="absolute w-8 h-8 bg-yellow-500/20 text-yellow-500 border border-yellow-500/40 rounded-full flex items-center justify-center text-[9px] font-mono hover:scale-115 transition" style={{ left: `${coin.x}%`, top: '50px' }}>
                                +{coin.val}
                              </button>
                            ))}
                            {tapTimeLeft === 0 && (
                              <div className="absolute inset-0 bg-slate-900/90 flex flex-col items-center justify-center text-center gap-2">
                                <span className="text-xs font-bold text-white">Sesi Selesai!</span>
                                <span className="text-sm font-black text-emerald-400">Rp {tapScore.toLocaleString()} Diraih</span>
                                <button onClick={() => {
                                  setUser(p => ({ ...p, main_balance: p.main_balance + tapScore }));
                                  addLedger('game_reward', tapScore, 'Reward Coin Catcher Winner');
                                  setActiveGame(null);
                                }} className="px-4 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-[10px] font-bold">Klaim Payout</button>
                              </div>
                            )}
                          </div>
                        </div>
                      )}

                    </div>
                  )}
                </div>
              )}

              {/* D. BONUS CLAIM CALENDAR */}
              {selectedSubPage === 'calendar_screen' && (
                <div className="py-2 flex flex-col gap-4 animate-fade-in text-slate-300">
                  <div className="flex justify-between items-center border-b border-slate-800 pb-2">
                    <h3 className="font-bold text-sm text-emerald-400">Hadiah Claim Kalender Harian</h3>
                    <button onClick={() => setSelectedSubPage(null)}><X size={16}/></button>
                  </div>
                  <p className="text-[10px] text-slate-400">Lakukan absen checkout harian di bawah untuk mengoleksi saldo belanja gratis:</p>

                  <div className="grid grid-cols-4 gap-2.5">
                    {[1, 2, 3, 4, 5, 6, 7].map(day => {
                      const reward = day * 1500;
                      const isClaimed = claimedDays.includes(day);
                      return (
                        <button key={day} disabled={isClaimed} onClick={() => claimDailyDay(day, reward)} className={`p-2.5 rounded-xl border text-center flex flex-col items-center gap-1 transition ${isClaimed ? 'bg-slate-950 border-slate-850 opacity-40 text-slate-500' : 'bg-slate-900 border-slate-800 hover:border-emerald-400 text-slate-300'}`}>
                          <span className="text-[8px] uppercase tracking-wider block">Hari {day}</span>
                          <span className="text-xs font-bold">+{reward/1000}k</span>
                          <span className="text-[8px] bg-slate-950 px-1 py-0.5 rounded text-emerald-500 font-mono mt-1">{isClaimed ? 'CLAIMED' : 'KLAIM'}</span>
                        </button>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* E. HELPLINE GUIDES SCREEN */}
              {selectedSubPage === 'guide_screen' && (
                <div className="py-2 flex flex-col gap-4 animate-fade-in text-slate-300 text-xs">
                  <div className="flex justify-between items-center border-b border-slate-800 pb-2">
                    <h3 className="font-bold text-sm text-blue-400">Pertanyaan Umum (FAQ)</h3>
                    <button onClick={() => setSelectedSubPage(null)}><X size={16}/></button>
                  </div>

                  <div className="flex flex-col gap-3">
                    <div className="bg-slate-900 p-3.5 rounded-xl border border-slate-800 flex flex-col gap-1.5">
                      <span className="font-bold text-white block">Apakah saldo bonus gratis bisa langsung ditarik?</span>
                      <p className="text-[10px] text-slate-400 leading-normal">Tidak bisa. Saldo bonus pendaftaran Rp 15.000 hanya berfungsi untuk pembelanjaan / penyewaan produk miner pertambangan. Hasil dividen pertambangan harian Anda dapat ditarik.</p>
                    </div>

                    <div className="bg-slate-900 p-3.5 rounded-xl border border-slate-800 flex flex-col gap-1.5">
                      <span className="font-bold text-white block">Bagaimana sistem kenaikan level member VIP?</span>
                      <p className="text-[10px] text-slate-400 leading-normal">Kenaikan tingkat dihitung otomatis oleh sistem database kami berdasarkan total akumulasi isi ulang sukses (approved deposit). VIP 1 min Rp 50.000, VIP 2 min Rp 100.000, dan VIP 3 min Rp 1.000.000.</p>
                    </div>
                  </div>
                </div>
              )}

              {/* F. APK COMPLIANCE SCREEN */}
              {selectedSubPage === 'apk_screen' && (
                <div className="py-4 flex flex-col gap-5 text-center animate-fade-in">
                  <div className="w-16 h-16 bg-blue-500/10 rounded-full flex items-center justify-center border border-blue-500/30 text-blue-500 mx-auto">
                    <Download size={32}/>
                  </div>
                  <h3 className="text-sm font-bold text-white">Unduh Google Client APK</h3>
                  <p className="text-xs text-slate-400 leading-normal px-2">Silakan unduh file instalasi APK alternatif di bawah untuk mempermudah operasional server pertambangan NOXARA Anda di semua smartphone.</p>
                  <button onClick={() => alert('Simulator: File APK NOXARA akan diunduh ke ponsel')} className="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded-xl text-xs uppercase tracking-wider">Mulai Mengunduh APK (12.4 MB)</button>
                </div>
              )}

              {/* G. EVENTS BANNER DISPLAY */}
              {selectedSubPage === 'promo_screen' && (
                <div className="py-2 flex flex-col gap-4 animate-fade-in text-slate-300">
                  <div className="flex justify-between items-center border-b border-slate-800 pb-2">
                    <h3 className="font-bold text-sm text-indigo-400">Promo Dan Kegiatan</h3>
                    <button onClick={() => setSelectedSubPage(null)}><X size={16}/></button>
                  </div>
                  <div className="bg-slate-900 border border-slate-800 p-4 rounded-xl">
                    <span className="text-[10px] bg-indigo-500/20 text-indigo-400 px-2 py-0.5 rounded font-bold uppercase block w-max mb-2">Event Aktif</span>
                    <h4 className="text-xs font-bold text-white">Tambang Berlipat Berkecepatan Tinggi</h4>
                    <p className="text-[10px] text-slate-400 mt-1 leading-relaxed">Mulai operasional komparasi Miner Tier Medium untuk tambahan komisi bonus referral upline +15% disetiap pembelian bawahan.</p>
                  </div>
                </div>
              )}

              {/* H. REAL-TIME SUPPORT INBOX CHAT */}
              {selectedSubPage === 'live_chat' && (
                <div className="py-2 flex flex-col gap-3 animate-fade-in min-h-[440px] flex-grow justify-between">
                  <div className="flex justify-between items-center border-b border-slate-800 pb-2 shrink-0">
                    <h3 className="font-bold text-sm text-blue-400">Live Support Chat 24/7</h3>
                    <button onClick={() => setSelectedSubPage(null)}><X size={16}/></button>
                  </div>

                  <div className="flex-grow bg-slate-950 rounded-2xl p-4 border border-slate-900 max-h-[300px] overflow-y-auto flex flex-col gap-2">
                    {chatMessages.map((msg, i) => (
                      <div key={i} className={`p-3 rounded-2xl max-w-[85%] text-xs ${msg.sender === 'user' ? 'bg-blue-600 text-white self-end' : 'bg-slate-900 text-slate-300 self-start border border-slate-800'}`}>
                        <p className="leading-normal">{msg.message}</p>
                        <span className="text-[8px] opacity-60 block mt-1 text-right">{msg.time}</span>
                      </div>
                    ))}
                  </div>

                  <form onSubmit={sendSupportMsg} className="flex gap-2 shrink-0">
                    <input type="text" placeholder="Tulis rincian keluhan Anda di sini..." value={txtMsg} onChange={e => setTxtMsg(e.target.value)} className="flex-grow bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    <button type="submit" className="bg-blue-600 hover:bg-blue-500 text-white rounded-xl px-3 font-bold text-xs"><Send size={15}/></button>
                  </form>
                </div>
              )}

              {/* I. BANK ACCOUNTS SETUP WRAPPERS */}
              {selectedSubPage === 'bank_account' && (
                <div className="py-2 flex flex-col gap-4 animate-fade-in">
                  <div className="flex justify-between items-center border-b border-slate-800 pb-2">
                    <h3 className="font-bold text-sm text-blue-500">Ikat Buku Rekening Bank</h3>
                    <button onClick={() => setSelectedSubPage(null)}><X size={16}/></button>
                  </div>

                  <form onSubmit={saveBankAccount} className="bg-slate-900 p-5 rounded-2xl border border-slate-840 flex flex-col gap-4">
                    <div>
                      <label className="text-[10px] text-slate-400 uppercase font-black block mb-1">Pilihan Nama Bank / Dompet Digital</label>
                      <input type="text" required placeholder="Contoh: BCA, MANDIRI, DANA, OVO" value={user.bankName} onChange={e => setUser({...user, bankName: e.target.value})} className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                    <div>
                      <label className="text-[10px] text-slate-400 uppercase font-black block mb-1">Nomor Rekening Akun</label>
                      <input type="number" required placeholder="Masukkan nomor rekening atau nomor HP" value={user.bankAccount} onChange={e => setUser({...user, bankAccount: e.target.value})} className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                    <div>
                      <label className="text-[10px] text-slate-400 uppercase font-black block mb-1">Nama Pemilik Akun Sesuai Rekening</label>
                      <input type="text" required placeholder="Contoh: Jaka Pratama" value={user.bankHolder} onChange={e => setUser({...user, bankHolder: e.target.value})} className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                    <div>
                      <label className="text-[10px] text-slate-400 uppercase font-black block mb-1">Kata Sandi Akun (Verifikasi Keamanan)</label>
                      <input type="password" required placeholder="Kata sandi saat daftar" className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <button type="submit" className="w-full py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl tracking-wider uppercase">Simpan Data Rekening</button>
                  </form>
                </div>
              )}

              {/* J. WITHDRAWAL PIN WRAPPER */}
              {selectedSubPage === 'pin_setup' && (
                <div className="py-2 flex flex-col gap-4 animate-fade-in">
                  <div className="flex justify-between items-center border-b border-slate-800 pb-2">
                    <h3 className="font-bold text-sm text-blue-500">Buat PIN Transaksi Keamanan</h3>
                    <button onClick={() => setSelectedSubPage(null)}><X size={16}/></button>
                  </div>

                  <form onSubmit={createPIN} className="bg-slate-900 p-5 rounded-2xl border border-slate-840 flex flex-col gap-4">
                    <div>
                      <label className="text-[10px] text-slate-400 uppercase font-bold block mb-1 font-mono">Masukkan PIN Baru (6 Digit)</label>
                      <input type="password" required maxLength={6} placeholder="Angka sahaja" value={user.pin} onChange={e => setUser({...user, pin: e.target.value})} className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white text-center font-mono focus:ring-1 focus:ring-blue-500" />
                    </div>
                    <div>
                      <label className="text-[10px] text-slate-400 uppercase font-bold block mb-1 font-mono">Konfirmasi Sandi PIN Baru</label>
                      <input type="password" required maxLength={6} placeholder="Ulangi angka" className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white text-center font-mono focus:ring-1 focus:ring-blue-500" />
                    </div>
                    <div>
                      <label className="text-[10px] text-slate-400 uppercase font-bold block mb-1">Konfirmasi Kata Sandi Akun</label>
                      <input type="password" required placeholder="Verifikasi sandi masuk" className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <button type="submit" className="w-full py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl tracking-wider uppercase">Aktifkan Kunci PIN</button>
                  </form>
                </div>
              )}

              {/* K. CASHIFY INSTANT DEPOSIT INVOICE FLOW */}
              {selectedSubPage === 'topup' && (
                <div className="py-2 flex flex-col gap-4 animate-fade-in">
                  <div className="flex justify-between items-center border-b border-slate-800 pb-2">
                    <h3 className="font-bold text-sm text-blue-500">Deposit Virtual Instan</h3>
                    <button onClick={() => setSelectedSubPage(null)}><X size={16}/></button>
                  </div>

                  {!activeInvoice ? (
                    <div className="bg-slate-900 p-5 rounded-2xl border border-slate-800 flex flex-col gap-4">
                      <div>
                        <label className="text-[10px] text-slate-400 uppercase block mb-1">Nominal yang diajukan (Rp):</label>
                        <input type="number" placeholder="Contoh: 50000" value={depositAmount} onChange={e => setDepositAmount(e.target.value)} className="w-full bg-slate-950 border border-slate-805 rounded-xl px-3 py-2.5 text-xs text-white font-mono focus:ring-1 focus:ring-blue-500" />
                      </div>

                      {/* Quick shortcut amounts */}
                      <div className="grid grid-cols-3 gap-2">
                        {[50000, 100000, 200000].map(val => (
                          <button key={val} type="button" onClick={() => setDepositAmount(String(val))} className="bg-slate-950 border border-slate-850 py-1.5 rounded-lg text-[10px] text-slate-300 font-bold hover:border-blue-500 transition">Rp {val/1000}k</button>
                        ))}
                      </div>

                      <div>
                        <label className="text-[10px] text-slate-400 uppercase block mb-2">Metode Visual (Hanya untuk Tampilan):</label>
                        <div className="grid grid-cols-2 gap-2">
                          {['QRIS Instant', 'BCA virtual', 'MANDIRI VA', 'DANA E-wallet'].map(brand => (
                            <button key={brand} type="button" onClick={() => setDepositMethod(brand)} className={`p-2.5 rounded-xl border text-[10px] text-left transition font-mono ${depositMethod === brand ? 'bg-gradient-to-r from-blue-900 to-cyan-900 border-blue-500 text-white font-bold' : 'bg-slate-950 border-slate-850 text-slate-400 hover:border-slate-700'}`}>
                              {brand}
                            </button>
                          ))}
                        </div>
                      </div>

                      <button onClick={() => {
                        const amountFloat = parseFloat(depositAmount);
                        if (!amountFloat || amountFloat < 20000) {
                          alert('Jumlah nominal minimal deposit Rp 20.000!');
                          return;
                        }
                        submitDeposit(amountFloat);
                      }} className="w-full py-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:opacity-90 rounded-xl text-xs font-black text-white uppercase tracking-wider mt-1.5 shadow-lg">Buat Invoice QRIS Cashify</button>
                    </div>
                  ) : (
                    <div className="bg-slate-900 p-5 rounded-xl border border-slate-800 flex flex-col gap-4 text-center">
                      <span className="text-[10px] bg-yellow-500/10 text-yellow-500 border border-yellow-500/30 px-2 py-0.5 rounded-full w-max mx-auto font-mono">Invoice Tagihan Menunggu Pembayaran</span>
                      
                      <div className="flex flex-col gap-1 my-1">
                        <span className="text-xs text-slate-400 font-bold">Lakukan Pembayaran Sebesar:</span>
                        <span className="text-xl font-mono font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-blue-500">Rp {activeInvoice.totalAmount.toLocaleString()}</span>
                        <span className="text-[9px] text-yellow-500 font-bold">*Wajib mentransfer sesuai angka unik guna penyelarasan cepat!</span>
                      </div>

                      {/* Fake static QRIS loader from Cashify rules */}
                      <div className="w-44 h-44 bg-white p-3 rounded-2xl mx-auto flex items-center justify-center border-4 border-slate-850 shadow-inner">
                        <div className="bg-indigo-950 w-full h-full rounded flex flex-col items-center justify-center">
                          <span className="text-[10px] text-white font-black font-mono">CASHIFY QR MAP</span>
                          <span className="text-[8px] text-slate-300 font-mono mt-1">com.orderkuota.app</span>
                        </div>
                      </div>

                      <div className="flex flex-col gap-2 border-t border-slate-800 pt-4 text-left font-mono">
                        <div className="flex justify-between text-[10px]">
                          <span className="text-slate-400">ID Transaksi:</span>
                          <span className="text-slate-300">{activeInvoice.txId}</span>
                        </div>
                        <div className="flex justify-between text-[10px]">
                          <span className="text-slate-400">Metode Tag:</span>
                          <span className="text-slate-300">{depositMethod}</span>
                        </div>
                        <div className="flex justify-between text-[10px]">
                          <span className="text-slate-400">Sisa Expired Countdown:</span>
                          <span className="text-red-400 font-bold">{activeInvoice.expiresAt} WITA</span>
                        </div>
                      </div>

                      <div className="grid grid-cols-2 gap-2 mt-2">
                        <button disabled={checkingPayment} onClick={verifyPaymentSimulation} className="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 rounded-xl text-xs flex justify-center items-center gap-1">
                          {checkingPayment ? 'Polling...' : <RefreshCw size={12}/>} Cek Bayar
                        </button>
                        <button onClick={() => {
                          setActiveInvoice(null);
                          setSuccessFlash('Invoice berhasil dibatalkan hancur.');
                        }} className="w-full bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-2 rounded-xl text-xs">Batalkan</button>
                      </div>
                    </div>
                  )}
                </div>
              )}

              {/* L. PENARIKAN DANA PROFILES */}
              {selectedSubPage === 'withdraw' && (
                <div className="py-2 flex flex-col gap-4 animate-fade-in text-slate-300">
                  <div className="flex justify-between items-center border-b border-slate-800 pb-2">
                    <h3 className="font-bold text-sm text-blue-500">Penarikan Dana Payout</h3>
                    <button onClick={() => setSelectedSubPage(null)}><X size={16}/></button>
                  </div>

                  {(!user.bankName || !user.pin) ? (
                    <div className="bg-slate-900 p-5 rounded-2xl border border-slate-800 text-center flex flex-col gap-3">
                      <div className="w-12 h-12 bg-amber-500/10 border border-amber-500/20 text-amber-500 rounded-full flex items-center justify-center mx-auto">
                        <Lock size={20}/>
                      </div>
                      <div>
                        <h4 className="font-bold text-sm text-white">Kelengkapan Keamanan Kurang</h4>
                        <p className="text-[10px] text-slate-400 mt-1 max-w-[90%] mx-auto leading-relaxed">Anda wajib mengaitkan rekening bank, serta mengatur 6 digit PIN transaksi terlebih dahulu sebelum mengajukan kas keluar.</p>
                      </div>
                      <button onClick={() => setSelectedSubPage('bank_account')} className="bg-blue-600 hover:bg-blue-500 py-1.5 font-bold rounded-xl text-xs text-white">Lengkapi Sekarang</button>
                    </div>
                  ) : (
                    <form onSubmit={e => {
                      e.preventDefault();
                      const val = parseFloat(depositAmount);
                      if (!val || val > user.main_balance) {
                        alert('Saldo Utama Anda tidak mencukupi atau nominal tidak valid!');
                        return;
                      }

                      // Deduct and move securely to locked requests pool
                      setUser(p => ({
                        ...p,
                        main_balance: p.main_balance - val,
                        locked_balance: p.locked_balance + val
                      }));

                      addLedger('withdraw_request', val, `Permohonan withdraw dimasukan ke antrean divalidasi admin`);
                      setSuccessFlash('Penarikan dana tunai dimasukan antrean! Admin akan memantau dalam 1x24 jam.');
                      setSelectedSubPage(null);
                      playBeep(440, 0.2);
                    }} className="bg-slate-900 p-5 rounded-2xl border border-slate-800 flex flex-col gap-4">
                      <div className="bg-slate-950 p-3.5 rounded-xl border border-slate-850 font-mono text-[10px] text-slate-400">
                        <span className="block text-slate-500">Buku Tujuan Transfer Terikat:</span>
                        <span className="font-bold text-slate-200 mt-0.5 block">{user.bankName} - {user.bankAccount} ({user.bankHolder})</span>
                      </div>

                      <div>
                        <label className="text-[10px] text-slate-400 uppercase block mb-1">Nominal ditarik (Rp):</label>
                        <input type="number" required placeholder="Minimal Rp 50.000" className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono" />
                      </div>

                      <div>
                        <label className="text-[10px] text-slate-400 uppercase block mb-1">Sandi PIN Transaksi (6 digit):</label>
                        <input type="password" required maxLength={6} className="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono text-center" />
                      </div>

                      <button type="submit" className="w-full py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl tracking-wider uppercase">Konfirmasi Ajukan WD</button>
                    </form>
                  )}
                </div>
              )}

            </div>
          )}

        </div>

        {/* PERSISTENT WEB-APP BOTTOM BAR (6 BUTTONS: Home, Tim, +/Produk, Mining, Transaksi, Profil) */}
        {activeScreen === 'app' && (
          <nav className="absolute bottom-0 inset-x-0 bg-slate-900 border-t border-slate-800 h-16 grid grid-cols-6 items-center z-45 text-center text-slate-400 text-[9px] font-sans shadow-inner">
            <button onClick={() => {
              setCurrentTab('home');
              setSelectedSubPage(null);
              playBeep(500, 0.05);
            }} className={`flex flex-col items-center gap-1 py-1 hover:text-blue-400 ${currentTab === 'home' && !selectedSubPage ? 'text-blue-500 font-extrabold' : ''}`}>
              <Zap size={16} />
              <span>Home</span>
            </button>

            <button onClick={() => {
              setCurrentTab('team');
              setSelectedSubPage(null);
              playBeep(500, 0.05);
            }} className={`flex flex-col items-center gap-1 py-1 hover:text-blue-400 ${currentTab === 'team' && !selectedSubPage ? 'text-blue-500 font-extrabold' : ''}`}>
              <Users size={16} />
              <span>Tim</span>
            </button>

            {/* Middle Product Button Plus Area matching layout mandate perfectly */}
            <button onClick={() => {
              setCurrentTab('product');
              setSelectedSubPage(null);
              playBeep(500, 0.05);
            }} className="flex flex-col items-center translate-y-[-12px] z-10 transition">
              <div className={`w-11 h-11 rounded-full flex items-center justify-center border-4 border-slate-950 shadow-md ${currentTab === 'product' ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'}`}>
                <Play size={18} className="rotate-90" />
              </div>
              <span className={`text-[8px] mt-0.5 ${currentTab === 'product' ? 'text-blue-500 font-extrabold' : 'text-slate-400'}`}>Produk</span>
            </button>

            <button onClick={() => {
              setCurrentTab('mining');
              setSelectedSubPage(null);
              playBeep(500, 0.05);
            }} className={`flex flex-col items-center gap-1 py-1 hover:text-blue-400 ${currentTab === 'mining' && !selectedSubPage ? 'text-blue-500 font-extrabold' : ''}`}>
              <Settings size={16} />
              <span>Mining</span>
            </button>

            <button onClick={() => {
              setCurrentTab('tx');
              setSelectedSubPage(null);
              playBeep(500, 0.05);
            }} className={`flex flex-col items-center gap-1 py-1 hover:text-blue-400 ${currentTab === 'tx' && !selectedSubPage ? 'text-blue-500 font-extrabold' : ''}`}>
              <History size={16} />
              <span>Transaksi</span>
            </button>

            <button onClick={() => {
              setCurrentTab('profile');
              setSelectedSubPage(null);
              playBeep(500, 0.05);
            }} className={`flex flex-col items-center gap-1 py-1 hover:text-blue-400 ${currentTab === 'profile' && !selectedSubPage ? 'text-blue-500 font-extrabold' : ''}`}>
              <User size={16} />
              <span>Profil</span>
            </button>
          </nav>
        )}

      </div>

      {/* FLOAT DEV CONTROLLER PANELS / DEVELOPER CRON & LEDGER EMULATOR */}
      <div className="w-full max-w-[430px] mt-4 bg-slate-900 border border-slate-800 p-4 rounded-3xl text-xs text-slate-300 flex flex-col gap-3 font-mono">
        <div className="flex justify-between items-center border-b border-slate-800 pb-2">
          <span className="text-[10px] font-black text-cyan-400 uppercase tracking-widest flex items-center gap-1">
            <RefreshCw size={12} className="animate-spin" /> aaPanel Dev Sandbox Console
          </span>
          <button onClick={() => setIsAdminPanelOpen(!isAdminPanelOpen)} className="bg-slate-800 px-2 py-0.5 rounded text-[10px] hover:bg-slate-700 transition">
            {isAdminPanelOpen ? 'HIDE' : 'EXPAND'}
          </button>
        </div>

        {isAdminPanelOpen && (
          <div className="flex flex-col gap-3">
            <p className="text-[10px] text-slate-500 leading-normal">Gunakan tombol sandbox di bawah untuk melakukan uji kelayakan, menyinkronkan profit mining harian, mendelegasikan dana masuk, atau me-restart cron VPS:</p>
            
            <div className="grid grid-cols-2 gap-2 text-center text-[10px]">
              <button onClick={() => {
                setUser(prev => ({ ...prev, main_balance: prev.main_balance + 100000 }));
                addLedger('admin_credit', 100000, 'Kredit Tambahan Saldo Utama Admin Sandbox');
                logToAdmin('Injected Rp100,000 into Main Balance.');
                playBeep(900, 0.1);
              }} className="bg-blue-600/20 text-blue-400 border border-blue-500/30 p-2 rounded-xl font-bold hover:bg-blue-600/30">Tambah Rp100k</button>

              <button onClick={() => {
                // Instantly execute cashify polling cron check
                logToAdmin('Checking outstanding top-up sessions against Cashify webhook APIs...');
                if (activeInvoice) {
                  verifyPaymentSimulation();
                } else {
                  logToAdmin('No pending invoices found matching com.orderkuota.app.');
                }
              }} className="bg-cyan-600/20 text-cyan-400 border border-cyan-500/30 p-2 rounded-xl font-bold hover:bg-cyan-600/30">Simulasi Cashify Cron</button>

              <button onClick={() => {
                logToAdmin('Spawning daily cron cycle checking lease durations...');
                setTimeout(() => {
                  logToAdmin('Cron checking user_products active inventories. All is up to date.');
                }, 800);
              }} className="bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 p-2 rounded-xl font-bold hover:bg-indigo-600/30">Run Lease Cron</button>

              <button onClick={() => {
                // Force sync VIP Level drills
                logToAdmin('Scanning accumulated payments logs to calibrate VIP standings.');
                setUser(p => {
                  const sumVal = p.total_topup;
                  let targetVip = 0;
                  if (sumVal >= 1000000) targetVip = 3;
                  else if (sumVal >= 100000) targetVip = 2;
                  else if (sumVal >= 50000) targetVip = 1;
                  logToAdmin(`Calibrated VIP level status at VIP ${targetVip}`);
                  return { ...p, vip: targetVip };
                });
              }} className="bg-purple-600/20 text-purple-400 border border-purple-500/30 p-2 rounded-xl font-bold hover:bg-purple-600/30">Sync VIP Doctor</button>
            </div>

            <div className="bg-slate-950 p-3 rounded-xl border border-slate-800 flex flex-col gap-1.5 h-32 overflow-y-auto">
              {adminLog.map((log, i) => (
                <div key={i} className="text-[9px] text-slate-400 leading-tight">
                  <span className="text-cyan-500">{log.split(' ')[0]}</span> {log.split(' ').slice(1).join(' ')}
                </div>
              ))}
            </div>
          </div>
        )}
      </div>

    </div>
  );
}
