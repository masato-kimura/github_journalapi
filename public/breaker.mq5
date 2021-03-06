//+------------------------------------------------------------------------+
//|                                                      breaker.mq5       |
//|                        Copyright 2017, RoundAbout All Rights Reserved. |
//|                                             https://payjournal.net     |
//+------------------------------------------------------------------------+
#property copyright "Copyright 2017, RoundAbout All Rights Reserved."
#property link      "https://payjournal.net"
#property version   "1.00"
#include<Trade\Trade.mqh>
CTrade trade; // トレードクラス https://www.mql5.com/ja/docs/standardlibrary/tradeclasses/ctrade
MqlTick last_tick;

#define S "short"
#define L "long"

input double lot = 1;                     // ロット数 1万枚は0.1と設定する
input int    ticks = 60;                  // 最高値、最安値を算出するための対象ティック数
input double loss_cut_point = 2000;       // 設定ロスカット 2000=200.0pips  
input int    brank_times = 1;             // ブランク本数
input int    plus_gmt_hour = 2;           // FX取引業者がサーバーを設置している国の冬時間のグリニッジ標準時(gmt)との差分。例）XMの場合は冬+2時間。
input bool   is_summertime_exists = true; // FX取引業者がサーバーを設置している国がサマータイムを実施している場合はtrue
//input int    to_volatility_ticks = 20;   // 平均ボラティリティを算出するための対象ティック数

bool   process = true;                     // プログラム動作フラグ
ulong  deviation_point = 10;               // 偏差ポイント
double loss_cut_margin = 200;              // ロスカット設定値のマージン(逆指値にはこの値を追加する。成行で損切するため。) 200=20pips
int    entry_times = 0;                    // 現在の追加エントリー回数
double take_profit_point = 50000;          // 利確ライン（ほぼ無制限）50000=5000.0pips 
double top_up_point = 800;                 // 増し玉ポイント 800=80.0pips, 0なら前日高値の5%
int    max_entry_times_of_day = 3;         // １日のエントリー回数上限

string direction;                          // 現在の買売 long または short
bool   is_no_position_long  = true;        // プログラム上での買いノーポジションフラグ
bool   is_no_position_short = true;        // プログラム上での売りノーポジションフラグ
int    brank_remaing_long  = 0;            // ブランク消化回数(買い)
int    brank_remaing_short = 0;            // ブランク消化回数(売り)
double high_value = 0;                     // x日間の高値格納
double low_value  = 0;                     // x日間の安値格納
double high_value_to_entry  = 0;           // エントリー時の対象高値(上積み含む)*
double low_value_to_entry   = 0;           // エントリー時の対象安値(上積み含む)*
double last_rate_high_value = 0;           // 前日の高値
bool   is_summertime = false;              // プログラム内で使用するサマータイムフラグ
//double volatility_value = 0;              // プログラム内で使用するボラティリティ値


ulong  arr_order_tickets_long[];           // valueはオーダーticket*
ulong  arr_order_tickets_short[];          // valueはオーダーticket*
double arr_stop_loss[];                    // keyは(int)オーダーticket, valueはストップロス価格*
double arr_take_profit[];                  // keyは(int)オーダーticket, valueは利確価格*

//+------------------------------------------------------------------+
//| Expert initialization function                                   |
//+------------------------------------------------------------------+
int OnInit()
{
   Print("[start]", __FUNCTION__);
   
   int MagicNumber = 696969;
   trade.SetExpertMagicNumber(MagicNumber);
   trade.SetDeviationInPoints(deviation_point);
   trade.SetTypeFilling(ORDER_FILLING_RETURN);
   trade.LogLevel(1);
   trade.SetAsyncMode(false);
   
   GetMaximunHighLowValues();
   InquirySummertime();
   //GetVolatility();
   
   return(INIT_SUCCEEDED);
}
//+------------------------------------------------------------------+
//| Expert deinitialization function                                 |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
//--- Release our indicator handles
}
//+------------------------------------------------------------------+
//| Expert tick function                                             |
//+------------------------------------------------------------------+
void OnTick()
{
   if ( ! process) return;   
   
   // 週末決済
   if ( ! WeekEndCheck()) return;  
   
   // 日毎決済
   if ( ! DailyEndCheck()) return; 
   
   if (Bars(_Symbol, _Period) < 100)
   {
      Alert("We have less than 100 bars, EA will now exit!! ", __FUNCTION__);
      return;
   }

   if ( ! SymbolInfoTick(_Symbol, last_tick))
   {
      Alert("現在価格を取得できません:",GetLastError(),"!! ", __FUNCTION__);
      return;
   }

   // 新しいローソク足生成時
   if (NewTickGenerateCheck())
   {
      GetMaximunHighLowValues();
      InquirySummertime();
      //GetVolatility();
   }

   // ロスカット
   LossCut();

   // エントリー実施
   Entry();
   
   return;
}


void Entry()
{
   // 増し玉ポイント
   double _top_up_point = GetTopUpPoint();
   
   // 買いエントリー
   if (last_tick.ask > high_value_to_entry)
   {
      // エントリー回数上限チェック
      entry_times = entry_times + 1;
      if (entry_times > max_entry_times_of_day) return;

      // ノーポジ状態でブランク回数が消化されていない場合は処理しない。
      if (is_no_position_long == true && brank_remaing_long > 0) return;
      brank_remaing_long = brank_times;

      if (is_no_position_long == true)
      {
         // 新規買いエントリー
         SetBuyPosition(last_tick.ask);
         
         // 次回エントリー買い値を更新
         high_value_to_entry = high_value_to_entry + _top_up_point;
      }
      else
      {
         Print("増し玉買いエントリ +", _top_up_point / _Point, " point");

         // エントリー済みポジションの損切値（トレーリングストップ）を更新
         ModifyBuyPosition();

         // 追加買いエントリー
         SetBuyPosition(last_tick.ask);
         
         // 次回エントリー買い値を更新
         high_value_to_entry = high_value_to_entry + _top_up_point;
      }
      
      return;
   }
   
   // 売りエントリー
   if (last_tick.bid < low_value_to_entry)
   {
      // エントリー回数上限チェック
      entry_times = entry_times + 1;
      if (entry_times > max_entry_times_of_day) return;
      
      // ブランク回数が消化されていない場合は処理しない。ただし当日ブランク回数がセットされたものは許可
      if (is_no_position_short == true && brank_remaing_short > 0) return;
      brank_remaing_short = brank_times;
    
      if (is_no_position_short == true)
      {
         // 新規売りエントリー
         SetSellPosition(last_tick.bid);
         
         // 次回エントリー売値を更新
         low_value_to_entry = low_value_to_entry - _top_up_point;
      }
      else
      {
         Print("増し玉売りエントリー +", _top_up_point / _Point, " point");

         // エントリー済みポジションの損切値（トレーリングストップ）を更新
         ModifySellPosition();

         // 追加売りエントリー
         SetSellPosition(last_tick.bid);
         
         // 次回エントリー売値を更新
         low_value_to_entry = low_value_to_entry - _top_up_point;
      }
      
      return;
   }
}


void LossCut()
{
   // 買いエントリー
   int _size_long = ArraySize(arr_order_tickets_long);
   int _ticket;
   bool _is_action = false;
   for(int i=0; i<_size_long; i++)
   {
      if (arr_order_tickets_long[i] > 0)
      {
         _ticket = (int)arr_order_tickets_long[i];
         if (arr_stop_loss[_ticket] > 0)
         {
            if (arr_stop_loss[_ticket] >= last_tick.bid)
            {
               ClosePositionByTicket(_ticket);
               arr_order_tickets_long[i] = 0;
               arr_stop_loss[_ticket] = 0;
               arr_take_profit[_ticket] = 0;
               _is_action = true;
            }
         }
      }
   }
   
   // 売りエントリー
   int size_short = ArraySize(arr_order_tickets_short);
   for(int j=0; j<size_short; j++)
   {
      if (arr_order_tickets_short[j] > 0)
      {
         _ticket = (int)arr_order_tickets_short[j];
         if (arr_stop_loss[_ticket] > 0)
         {
            if (arr_stop_loss[_ticket] <= last_tick.ask)
            {
               ClosePositionByTicket(_ticket);
               arr_order_tickets_short[j] = 0;
               arr_stop_loss[_ticket] = 0;
               arr_take_profit[_ticket] = 0;
               _is_action = true;
            }
         }
      }
   }
   
   // エントリ存在確認
   if (_is_action == true)
   {
      bool _is_entry_long  = false;
      bool _is_entry_short = false;
      
      _size_long = ArraySize(arr_order_tickets_long);
      for(int k=0; k<_size_long; k++)
      {
         if (arr_order_tickets_long[k] > 0)
         {
            _is_entry_long = true;
            break;
         }
      }
      if (_is_entry_long == false)
      {
         is_no_position_long = true;
         direction = "";
      }
      else
      {
         is_no_position_long = false;
      }
      
      int _size_short = ArraySize(arr_order_tickets_short);
      for(int l=0; l<_size_short; l++)
      {
         if (arr_order_tickets_short[l] > 0)
         {
            _is_entry_short = true;
            break;
         }
      }
      if (_is_entry_short == false)
      {
         is_no_position_short = true;
         direction = "";
      }
      else
      {
         is_no_position_short = false;
      }
   }

   return;   
}


/**
   買いオーダーメソッド
*/
void SetBuyPosition(double price)
{
   Print("[start]", __FUNCTION__);

   double _entry_price = NormalizeDouble(price, _Digits);
   double _take_profit = NormalizeDouble(price + (take_profit_point * _Point), _Digits);
   double _stop_loss   = NormalizeDouble(price - (loss_cut_point * _Point), _Digits);
   double _send_stop_loss = _stop_loss - (loss_cut_margin * _Point);
   Print("エントリ: ", _entry_price, ", 利確: ", _take_profit, ", 実損切: ", _stop_loss, ", 設定損切: ", _send_stop_loss);
   
   string comment = "Buy " + _Symbol + " " + DoubleToString(lot) + " at " + DoubleToString(_entry_price, _Digits);
   
   if ( ! trade.PositionOpen(_Symbol, ORDER_TYPE_BUY, lot, _entry_price, _send_stop_loss, _take_profit, comment))
   {
      Alert("PositionOpen() method failed. Return code=",trade.ResultRetcode(),
         ". Code description: ",trade.ResultRetcodeDescription(), " entry_price: ", _entry_price);
      if (trade.ResultRetcode() == 10019)
      {
         process = false;
      }
      return;
   }
   Print("PositionOpen() method executed successfully. Return code=",trade.ResultRetcode(),
      " (",trade.ResultRetcodeDescription(),")");

   MqlTradeResult mresult;
   trade.Result(mresult);
   int order_tickets_long_size = ArraySize(arr_order_tickets_long);
   ArrayResize(arr_order_tickets_long, order_tickets_long_size + 1);
   arr_order_tickets_long[order_tickets_long_size] = mresult.order;
   
   int ticket = (int)mresult.order;
   ArrayResize(arr_stop_loss, ticket + 1);
   arr_stop_loss[ticket] = _stop_loss;
   ArrayResize(arr_take_profit, ticket + 1);
   arr_take_profit[ticket] = _take_profit;
  
   is_no_position_long = false;
   direction    = L;
   
   Print(" -", direction, "- ", "EP: ", _entry_price, " SL: ", _stop_loss);

   return;
}

   
/**
   売りオーダーメソッド
*/
void SetSellPosition(double price)
{
   Print("[start]", __FUNCTION__);
   
   double _entry_price = NormalizeDouble(price, _Digits);
   double _take_profit = NormalizeDouble(price - (take_profit_point * _Point), _Digits);
   double _stop_loss   = NormalizeDouble(price + (loss_cut_point * _Point), _Digits);
   double _send_stop_loss = _stop_loss + (loss_cut_margin * _Point);
   Print("エントリ: ", _entry_price, ", 利確: ", _take_profit, ", 実損切: ", _stop_loss, ", 設定損切: ", _send_stop_loss);

   string comment = "Sell " + _Symbol + " " + DoubleToString(lot) + " at " + DoubleToString(_entry_price, _Digits);
   
   if ( ! trade.PositionOpen(_Symbol, ORDER_TYPE_SELL, lot, _entry_price, _send_stop_loss, _take_profit, comment))
   {
      Alert("PositionOpen() method failed. Return code=",trade.ResultRetcode(),
         ". Code description: ",trade.ResultRetcodeDescription(), " entry_price: ", _entry_price);
      if (trade.ResultRetcode() == 10019)
      {
         process = false;
      }
      return;
   }
   Print("PositionOpen() method executed successfully. Return code=",trade.ResultRetcode(),
      " (",trade.ResultRetcodeDescription(),")");

   MqlTradeResult mresult;
   trade.Result(mresult);
   int order_tickets_short_size = ArraySize(arr_order_tickets_short);
   ArrayResize(arr_order_tickets_short, order_tickets_short_size + 1);
   arr_order_tickets_short[order_tickets_short_size] = mresult.order;
   
   int ticket = (int)mresult.order;
   ArrayResize(arr_stop_loss, ticket + 1);
   arr_stop_loss[ticket] = _stop_loss;
   ArrayResize(arr_take_profit, ticket + 1);
   arr_take_profit[ticket] = _take_profit;
   
   is_no_position_short = false;
   direction = S;
   
   Print(" -", direction, "- ", "EP: ", _entry_price, " SL: ", _stop_loss);

   return;
}


void ModifyBuyPosition()
{
   Print("[start]", __FUNCTION__);  
   
   double _top_up_point = GetTopUpPoint();
   
   if (is_no_position_long == true) return;
   
   double _stop_loss;
   double _take_profit;
   
   int size = ArraySize(arr_order_tickets_long);
   ulong ticket;
   for(int i=0; i<size; i++)
   {
      ticket = arr_order_tickets_long[i];
      if (ticket > 0)
      {
         _stop_loss = NormalizeDouble(arr_stop_loss[(int)ticket] + _top_up_point, _Digits);
         _take_profit = NormalizeDouble(arr_take_profit[(int)ticket], _Digits);
         if ( ! trade.PositionModify(ticket, _stop_loss - (loss_cut_margin * _Point), _take_profit))
         {
            if (trade.ResultRetcode() != 10006 && trade.ResultRetcode() != 10009 )
            {
               Print("Error Position Modify Return code=",trade.ResultRetcode(),
                  ". Code description: ",trade.ResultRetcodeDescription(), " ", __FUNCTION__);
            }
            return;
         }
         arr_stop_loss[(int)ticket] = arr_stop_loss[(int)ticket] + _top_up_point;
         Print("Succeed! Position Modify Buy ", __FUNCTION__);
      }
   }
   
   return;
}


void ModifySellPosition()
{
   Print("[start]", __FUNCTION__);
   
   double _top_up_point = GetTopUpPoint();
   
   if (is_no_position_short == true) return;
   
   double _stop_loss;
   double _take_profit;
   
   int size = ArraySize(arr_order_tickets_short);
   ulong ticket;
   for(int i=0; i<size; i++)
   {
      ticket = arr_order_tickets_short[i];
      if (ticket > 0)
      {
         _stop_loss = NormalizeDouble(arr_stop_loss[(int)ticket] - _top_up_point, _Digits);
         _take_profit = NormalizeDouble(arr_take_profit[(int)ticket], _Digits);
         if ( ! trade.PositionModify(ticket, _stop_loss + (loss_cut_margin * _Point), _take_profit))
         {
            if (trade.ResultRetcode() != 10006 && trade.ResultRetcode() != 10009 )
            {
               Print("Error Position Modify Return code=",trade.ResultRetcode(),
                  ". Code description: ",trade.ResultRetcodeDescription(), " ", __FUNCTION__);
            }
            return;
         }
         arr_stop_loss[(int)ticket] = arr_stop_loss[(int)ticket] - _top_up_point;
         Print("Succeed! Position Modify Sell ", __FUNCTION__);
      }
   }
   
   return;
}


// 同一通貨の全ポジションをクローズする
void ClosePositionAll()
{
   Print("[start]", __FUNCTION__);
   
   if (is_no_position_long == true && is_no_position_short == true) return;
   int _size_long  = ArraySize(arr_order_tickets_long);
   int size_short = ArraySize(arr_order_tickets_short);
   for (int i=0; i<_size_long; i++)
   {
      if (arr_order_tickets_long[i] > 0)
      {
         if ( ! trade.PositionClose(arr_order_tickets_long[i], deviation_point))
         {
            if (trade.ResultRetcode() != 10006)
            {
               Print("Error Position Close Return code=",trade.ResultRetcode(),
                  ". Code description: ",trade.ResultRetcodeDescription());
            }
         }
      }
   }
   for (int j=0; j<size_short; j++)
   {
      if (arr_order_tickets_short[j] > 0)
      {
         if ( ! trade.PositionClose(arr_order_tickets_short[j], deviation_point))
         {
            if (trade.ResultRetcode() != 10006)
            {
               Print("Error Position Close Return code=",trade.ResultRetcode(),
                  ". Code description: ",trade.ResultRetcodeDescription());
            }
         }
      }
   }
   
   if ( ! trade.PositionClose(_Symbol, deviation_point))
   {
      if (trade.ResultRetcode() != 10006)
      {
         Print("Error Position Close Return code=",trade.ResultRetcode(),
            ". Code description: ",trade.ResultRetcodeDescription());
      }
   }
   
   // ブランク回数をセット
   if (direction == L)
   {
      brank_remaing_long = brank_times;
   }
   else if (direction == S)
   {
      brank_remaing_short = brank_times;
   }

   // ノーポジションフラグをtrueへ
   is_no_position_long  = true;
   is_no_position_short = true;
   direction = "";
   
   ArrayResize(arr_order_tickets_long, 0);
   ArrayResize(arr_order_tickets_short, 0);
   ArrayResize(arr_stop_loss, 0);
   ArrayResize(arr_take_profit, 0);
   
   ArrayInitialize(arr_order_tickets_long, 0);
   ArrayInitialize(arr_order_tickets_short, 0);
   ArrayInitialize(arr_stop_loss, 0);
   ArrayInitialize(arr_take_profit, 0);

   Print("Succeed! Position Close", __FUNCTION__);
   
   return;
}


// チケットを指定しポジションをクローズする
void ClosePositionByTicket(ulong ticket)
{
   Print("[start]", __FUNCTION__);
   
   if (is_no_position_long == true && is_no_position_short == true) return;
   if (ticket <= 0) return;

   if ( ! trade.PositionClose(ticket, deviation_point))
   {
      if (trade.ResultRetcode() != 10006)
      {
         Print("Error Position Close Return code=",trade.ResultRetcode(),
            ". Code description: ",trade.ResultRetcodeDescription());
      }
   }

   Print("Succeed! Position Close ticket: ", ticket, " ",  __FUNCTION__);
   
   return;
}


// 新しいローソク足が生成されたらtrue
bool NewTickGenerateCheck()
{
   static datetime Old_Time;
   
   datetime _arr_new_time[1];
   bool _is_new_bar = false;
   int _copied = CopyTime(_Symbol, _Period, 0, 1, _arr_new_time);
   if (_copied > 0)
   {
      if (Old_Time != _arr_new_time[0])
      {
         _is_new_bar = true;
         Old_Time = _arr_new_time[0];
         MqlDateTime stm;
         datetime _tm = TimeTradeServer(stm);
         int gmt_hour = GetGmtHour();
         int japanese_hour = GetJapaneseHourFromGmt(gmt_hour);
         Print("-->[start]", __FUNCTION__, " date: ", _tm, " / 日本時間 ", japanese_hour, "時", stm.min, "分");
      }
   }
   else
   {
      Alert("Error in copying historical times data, error =",GetLastError());
      ResetLastError();
      return false;
   }
   if (_is_new_bar == false) return false;
   int _mybars = Bars(_Symbol, _Period);
   if (_mybars < 100) return false;
   
   return true;
}


bool GetMaximunHighLowValues()
{
   double _high = 0;
   double _low  = 1000000;
   datetime _high_time;
   datetime _low_time;
   
   MqlRates rates[];
   ArraySetAsSeries(rates, true);
   int _copied = CopyRates(_Symbol, _Period, 0, ticks, rates);
   if (_copied > 0)
   {
      for (int i=0; i<_copied; i++)
      {
         if (_high < rates[i].high)
         {
            _high = rates[i].high;
            _high_time = rates[i].time;
         }
         if (_low > rates[i].low)
         {
            _low = rates[i].low;
            _low_time = rates[i].time;
         }
      }
      
      if (rates[1].high < high_value)
      {
         brank_remaing_long = brank_remaing_long - 1;
         if (brank_remaing_long <= 0)
         {
            brank_remaing_long = 0;
         }
      }
      
      if (rates[1].low > low_value)
      {
         brank_remaing_short = brank_remaing_short - 1;
         if (brank_remaing_short <= 0)
         {
            brank_remaing_short = 0;
         }
      }
      
      high_value = _high;
      high_value_to_entry = _high;
      low_value  = _low;
      low_value_to_entry = _low;
      entry_times = 0;
      
      last_rate_high_value = rates[1].high;
      
      Print(ticks, "本高値: ", high_value, " / date: ", _high_time, " | ", ticks, "本安値: ", low_value, " / date: ", _low_time, " | ", "前日高値：　", last_rate_high_value);
   }
   else
   {
      Alert("Failed to get history data for the symbol ",Symbol());
      ResetLastError();
      return false;
   }
   
   return true;
}


// 平均ボラティリティ取得
/*
bool GetVolatility()
{
   double _sum_value = 0;
   MqlRates rates[];
   ArraySetAsSeries(rates, true);
   int _copied = CopyRates(_Symbol, _Period, 0, to_volatility_ticks, rates);
   if (_copied > 0)
   {
      for (int i=0; i<_copied; i++)
      {
         _sum_value = _sum_value + (rates[i].high - rates[i].low);
      }
      volatility_value = NormalizeDouble(_sum_value/to_volatility_ticks, _Digits);
      Print(to_volatility_ticks, "本平均ボラティリティ: ", volatility_value / _Point, " point");
   }
   else
   {
      Alert("Failed to GetVolatility for the symbol ",Symbol());
      ResetLastError();
      
      return false;
   }
   
   return true;
}
*/


double GetTopUpPoint()
{
   if (top_up_point > 0)
   {
      return NormalizeDouble(top_up_point * _Point, _Digits);
   }
   
   // 前日の高値
   double _top_up_point = NormalizeDouble(last_rate_high_value * 0.005, _Digits);

   return _top_up_point;
}


bool WeekEndCheck()
{
   static bool _is_stop;
   MqlDateTime stm;
   datetime _tm = TimeCurrent(stm);
   int _japanese_hour = GetJapaneseHourFromGmt(GetGmtHour());
   int _japanese_dayofweek = GetJapaneseDayOfWeek(_japanese_hour);
   int _start_hour = 7;
   int _end_hour   = 6;
   if (is_summertime == true)
   {
      _start_hour = 6;
      _end_hour   = 5;
   }

   // 月
   if (_japanese_dayofweek == 1)
   {
      if (_japanese_hour == _start_hour && stm.min < 5 )
      {
         if (_is_stop == false)
         {
            Print("<<---- Monday Close!1: ", TimeCurrent(), " / 日本時間 ", _japanese_hour, "時", stm.min, "分");
            ClosePositionAll();
            _is_stop = true;
         }
      }
      else if (_japanese_hour < _start_hour)
      {
         if (_is_stop == false)
         {
            Print("<<---- Monday Close!2: ", TimeCurrent(), " / 日本時間 ", _japanese_hour, "時", stm.min, "分");
            ClosePositionAll();
            _is_stop = true;
         }
      }
      else
      {
         if (_is_stop == true)
         {
            _is_stop = false;
         }
      }
   }
   
   // 火～金
   if (_japanese_dayofweek >= 2 && _japanese_dayofweek <= 5)
   {
      _is_stop = false;
   }

   // 土
   if (_japanese_dayofweek == 6) // 土曜 5:45 (日本時間)
   {
      // 5時45分以上は停止
      if (_japanese_hour == _end_hour && stm.min >= 45)
      {
         if (_is_stop == false)
         {
            Print("<<---- Saturday Close!1: ", TimeCurrent(), " / 日本時間 ", _japanese_hour, "時", stm.min, "分");
            ClosePositionAll();
            _is_stop = true;
         }
      }
      else if (_japanese_hour > _end_hour)
      {
         if (_is_stop == false)
         {
            Print("<<---- Saturday Close!2: ", TimeCurrent(), " / 日本時間 ", _japanese_hour, "時", stm.min, "分");
            ClosePositionAll();
            _is_stop = true;
         }
      }
      else
      {
         _is_stop = false;
      }
   }
   
   // 日
   if (stm.day_of_week == 0)
   {
      if (_is_stop == false)
      {
         Print("<<---- Sunday Close!: ", TimeCurrent(), " / 日本時間 ", _japanese_hour, "時", stm.min, "分");
         ClosePositionAll();
         _is_stop = true;
      }
   }
   
   if (_is_stop == true)
   {
      return false;
   }
   
   return true;
}


bool DailyEndCheck()
{
   static bool _is_stop;
   MqlDateTime stm;
   datetime _tm = TimeTradeServer(stm);
   int _start_hour = 7;
   int _end_hour   = 5;
   if (is_summertime == true)
   {
      _start_hour = 6;
      _end_hour   = 4;
   }
   int _japanese_hour = GetJapaneseHourFromGmt(GetGmtHour());
   
   // 冬午前5時30分(夏午前4時30分)～午前6時は停止
   if (_japanese_hour >= _end_hour && _japanese_hour < _start_hour)
   {
      if (_japanese_hour == _end_hour)
      {
         if (stm.min >= 30)
         {
            if (_is_stop == false)
            {
               Print("<-- Daily end Close!: ", TimeCurrent(), " / 日本時間 ", _japanese_hour, "時", stm.min, "分");
               ClosePositionAll();
               _is_stop = true;
            }
         }
         else
         {
            _is_stop = false;
         }
      }
      else
      {
         if (_is_stop == false)
         {
            Print("<-- Daily end Close!: ", TimeCurrent(), " / 日本時間 ", _japanese_hour, "時", stm.min, "分");
            ClosePositionAll();
            _is_stop = true;
         }  
      }
   }
   else
   {
      _is_stop = false;
   }
   
   if (_is_stop == false)
   {
      return true;
   }
   
   return false;
}


int GetGmtHour()
{
   MqlDateTime stm;
   datetime _tm = TimeTradeServer(stm);
   int gmt_hour = stm.hour - plus_gmt_hour;
   if (is_summertime == true)
   {
      gmt_hour = gmt_hour - 1;
   }
   if (gmt_hour < 0)
   {
      gmt_hour = 24 + gmt_hour;
   }
   return gmt_hour;
}


int GetJapaneseHourFromGmt(int gmt_hour)
{
   int japanese_hour = gmt_hour + 9;
   if (japanese_hour > 23)
   {
      japanese_hour = japanese_hour - 24; 
   } 

   return japanese_hour;
}


int GetJapaneseDayOfWeek(int japanese_hour)
{
   MqlDateTime stm;
   datetime _tm = TimeTradeServer(stm);
   int japanese_dayofweek;
   if (japanese_hour < stm.hour)
   {
      japanese_hour = japanese_hour + 24;
   }
   int diff_hour = japanese_hour - stm.hour;
   if (stm.hour >= (24 - diff_hour))
   {
      japanese_dayofweek = stm.day_of_week + 1;
      return japanese_dayofweek;
   }
   
   return stm.day_of_week;
}


void InquirySummertime()
{
   if (is_summertime_exists == false)
   {
      is_summertime = false;
      return;
   }
   
   long arr_summertime[1100];
   ArrayInitialize(arr_summertime, 0);
   // key: +1000year,  val: [9=start],month,day,hour,[8=end],month,day,hour 
   arr_summertime[999]  = 90328018113101;
   arr_summertime[1000] = 90326018112901;
   arr_summertime[1001] = 90325018112801;
   arr_summertime[1002] = 90324018112701;
   arr_summertime[1003] = 90323018112601;
   arr_summertime[1004] = 90328018113101;
   arr_summertime[1005] = 90327018113001;
   arr_summertime[1006] = 90326018112901;
   arr_summertime[1007] = 90325018112801;
   arr_summertime[1008] = 90323018112601;
   arr_summertime[1009] = 90329018112501;
   arr_summertime[1010] = 90328018113101;
   arr_summertime[1011] = 90327018113001;
   arr_summertime[1012] = 90325018112801;
   arr_summertime[1013] = 90331018112701;
   arr_summertime[1014] = 90330018112601;
   arr_summertime[1015] = 90329018112501;
   arr_summertime[1016] = 90327018113001;
   arr_summertime[1017] = 90326018112901;
   arr_summertime[1018] = 90325018112801;
   arr_summertime[1019] = 90331018112701;
   arr_summertime[1020] = 90329018112501;
   arr_summertime[1021] = 90328018113101;
   arr_summertime[1022] = 90327018113001;
   arr_summertime[1023] = 90326018112901;
   arr_summertime[1024] = 90331018112701;
   arr_summertime[1025] = 90330018112601;
   arr_summertime[1026] = 90329018112501;
   arr_summertime[1027] = 90328018113101;
   arr_summertime[1028] = 90326018112901;
   arr_summertime[1029] = 90325018112801;
   arr_summertime[1030] = 90331018112701;
   
   MqlDateTime stm;
   datetime _tm = TimeTradeServer(stm);
   string year  = (string)stm.year;
   
   string month;
   if (stm.mon < 10)
   {
      month = "0" + (string)stm.mon;
   }
   else
   {
      month = (string)stm.mon;
   }
   
   string day;
   if (stm.day < 10)
   {
      day = "0" + (string)stm.day;
   }
   else
   {
      day = (string)stm.day;
   }
   
   string hour;
   if (stm.hour < 10)
   {
      hour = "0" + (string)stm.hour;
   }
   else
   {
      hour = (string)stm.hour;
   }
   
   string now = year + month + day + hour;
   string num = (string)arr_summertime[stm.year - 1000];
   string start = year + StringSubstr(num, 1, 6); 
   string end   = year + StringSubstr(num, 8, 6);
   int now_datetime   = (int)now;
   int start_datetime = (int)start;
   int end_datetime   = (int)end;
   
   if (now_datetime >= start_datetime && now_datetime <= end_datetime)
   {
      is_summertime = true;
   }
   else
   {
      is_summertime = false;
   }
   
   return;
}


