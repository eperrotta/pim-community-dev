import React, {FC} from 'react';
import {useTranslate} from "@akeneo-pim-community/legacy-bridge";
import styled from "styled-components";
import {ProgressBar} from "./ProgressBar";
import {
  computeTotalToDisplay,
  computeTipMessage,
  getProgressBarLevel
} from "../../../helper/Dashboard/KeyIndicatorProgressBar";
import {Tip, Tips} from "../../../../domain";

type Props = {
  tips: Tips;
  ratio?: number;
  total?: number;
  title?: string;
};

const KeyIndicator: FC<Props> = ({children, ratio, total, tips, title}) => {
  const translate = useTranslate();

  if (ratio === undefined || total === undefined || title === undefined) {
    return <></>;
  }

  const tip: Tip = computeTipMessage(tips, ratio);

  return (
    <Container>
      <Icon>{children}</Icon>
      <Content>
        <ProgressBar
          level={getProgressBarLevel(ratio)}
          light={ratio === 0 || (ratio >= 50 && ratio < 80)}
          percent={ratio}
          progressLabel={Math.round(ratio) + '%'}
          size="small"
          title={title}
        />
        <Text>
          {
            total > 0 &&
            translate(
              `akeneo_data_quality_insights.dqi_dashboard.key_indicators.products_to_work_on`,
              {count: computeTotalToDisplay(total).toString()},
              computeTotalToDisplay(total)
            )
          }
            &nbsp;
          <Link dangerouslySetInnerHTML={{
            __html: translate(tip.message, {link: tip.link || ''})
          }}/>
        </Text>
      </Content>
    </Container>
  );
}

const Container = styled.div`
  flex: 1 0 50%;
  display: flex;
  margin: 24px 0 0 0;
  max-width: 50%;
  
  :nth-child(odd) {
    padding-right: 20px;
  }
  :nth-child(even) {
    padding-left: 20px;
  }
`;

const Icon = styled.div`
  border-right: 1px solid ${({theme}) => theme.color.grey80};
  min-width: 64px;
  padding-top: 18px;
  height: 60px;
  text-align: center;
  margin-right: 20px;
`;

const Content = styled.div`
  flex-grow: 1;
`;

const Text = styled.div`
  color: ${({theme}) => theme.color.grey100};
  margin-top: 10px;
`;

const Link = styled.span`
  a {
    color: ${({theme}) => theme.color.purple100};
    text-decoration: underline ${({theme}) => theme.color.purple100};
  }
`;

export {KeyIndicator};
